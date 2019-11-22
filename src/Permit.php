<?php

namespace Prophecy\Permit;

use Permit\Exceptions\ModuleNotFoundException;
use Permit\Exceptions\PermissionNotFoundException;
use Permit\Models\Ability;
use Permit\Models\Module;
use Permit\Models\Permission;
use Permit\Models\Role;

class Permit
{
    private  $SESSION_ROLE_KEY      = '_user_role';

    private  $SESSION_ABILITIES_KEY = '_user_abilities';

    public function __construct()
    {
//        $this->SESSION_ABILITIES_KEY = config('session_abilities_key');
//        $this->SESSION_ROLE_KEY      = config('session_role_key');
    }

    public function createRole()
    {
        $data = [
            ['name' => 'admin'],
            ['name' => 'volunteer'],
            ['name' => 'user'],
            ['name' => 'super_admin'],
        ];

        return Role::insert($data);
    }

    public function findRole(string $name)
    {
        return Role::where('name',$name)->first();
    }

    public function allRoles()
    {
        return Role::all();
    }

    public function createPermission()
    {
        $data = [
            ['name' => 'create'],
            ['name' => 'read'],
            ['name' => 'edit'],
            ['name' => 'update'],
            ['name' => 'delete'],
            ['name' => 'force_delete']
        ];

        return Permission::insert($data);
    }

    public function findPermission($value,$column = 'name')
    {
        return Permission::where($column,$value)->first();
    }

    public function allPermissions()
    {
        return Permission::all();
    }

    public function createModule()
    {
        $data = [
            ['name' => 'user'],
            ['name' => 'campaign'],
            ['name' => 'payout'],
            ['name' => 'ad'],
        ];

        return Module::insert($data);
    }

    public function findModule($value,$column = 'name')
    {
        return Module::where($column,$value)->first();
    }

    public function allModules()
    {
        return Module::all();
    }

    public function findAbilitiesOf(string $role)
    {
        $role = $this->findRole($role);

        if($role) {
            $role->load(['modules' => function($module) {
                $module->with('permissions');
            }]);

            $abilities = [];

            $availablePermissions = Permission::select('id','name')->get();

            foreach($role->modules as $module) {

                $abilities[$module->name] = [];
                $data = [];
                foreach($availablePermissions as $permission) {
                    $hasPerm = false;

                    foreach($module->permissions as $perm) {
                        if($permission->id === $perm->id)  {
                            $hasPerm = true;
                            break;
                        }
                    }

                    $data[$permission->name] = $hasPerm;
                }

                $abilities[$module->name] = $data;
            }
            return $abilities;
        }

        return collect([]);
    }

    /**
     * @param $module
     * @param $permission
     * @param bool $findInSession
     * @return bool
     */
    public function can($module, $permission,bool $findInSession = true)
    {
        $method = '_findInSession';

        if(!$findInSession) {
            $method = '_findInDb';
        }

        return $this->{$method}($module,$permission);
    }

    private function _findInSession($module,$permission)
    {
        $data = session($this->SESSION_ABILITIES_KEY);

        if(!isset($data[$module])) {
            return false;
        }

        return (bool) $data[$module][$permission];
    }

    /**
     * @param $roleId
     * @param $module
     * @param $permission
     * @return bool
     * @throws ModuleNotFoundException
     * @throws PermissionNotFoundException
     */
    private function _findInDb($module, $permission)
    {
        $roleId = session()->get($this->SESSION_ROLE_KEY)['id'];

        if(false === $module instanceof Module) {
            $module = $this->findModule($module);
            if($module === null) {
                throw new ModuleNotFoundException;
            }
        }

        if(false === $permission instanceof Permission) {
            $permission = $this->findPermission($permission);
            if($permission === null) {
                throw new PermissionNotFoundException;
            }
        }

        return (bool) $this->_findAbility($roleId,$module->id,$permission->id);
    }

    /**
     * @param int $roleId
     * @param int $moduleId
     * @param int $permissionId
     * @return mixed
     */
    private function _findAbility(int $roleId, int $moduleId, int $permissionId)
    {
        return Ability::where('role_id',$roleId)->where('module_id',$moduleId)->where('permission_id',$permissionId)->first();
    }
}
