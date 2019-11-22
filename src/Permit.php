<?php

namespace Prophecy\Permit;

use Prophecy\Permit\Exceptions\{
    ModuleNotFoundException,
    ModuleNotFoundException,
    PermissionNotFoundException
}
use Prophecy\Permit\Models\Ability;
use Prophecy\Permit\Models\Module;
use Prophecy\Permit\Models\Permission;
use Prophecy\Permit\Models\Role;

class Permit
{
    private  $SESSION_ROLE_KEY;

    private  $SESSION_ABILITIES_KEY;

    public function __construct()
    {
       $this->SESSION_ABILITIES_KEY = config('permit.session_abilities_key');
       $this->SESSION_ROLE_KEY      = config('permit.session_role_key');
    }

    public function createRole(array $attributes)
    {
        return Role::create($attributes);
    }

    public function createMultipleRole(array $roles)
    {
        return Role::inset($roles);
    }

    public function findRole(string $name)
    {
        return $this->_findRole('name',$name);
    }

    public function findRoleById(int $id)
    {
        return $this->_findRole('id',$id);
    }

    private function _findRole(string $column,$value)
    {
        return Role::where($column,$value)->first();
    }

    public function allRoles()
    {
        return Role::all();
    }

    public function updateRole(array $attributes,int $id)
    {
        $role = $this->_findRole('id',$id);

        if(!$role) {
            throw new RoleNotFoundException;
        }

        return $role->update($attributes);
    }

    public function deleteRole(int $id)
    {
        $role = $this->_findRole('id',$id);

        if(!$role) {
            throw new RoleNotFoundException;
        }
        return $role->delete();
    }

    public function createPermission(array $attributes)
    {
        return Permission::create($attributes);
    }

    public function createMultiplePermission(array $permissions)
    {
        return Permission::insert($permissions);
    }

    private function _findPermission(string $column,$value)
    {
        return Permission::where($column,$value)->first();
    }

    public function findPermission(string $name)
    {
        return $this->_findPermission('name',$name);
    }

    public function findPermissionById(int $id)
    {
        return $this->_findPermission('id',$id);
    }

    public function allPermissions()
    {
        return Permission::all();
    }

    public function updatePermission(array $attributes,int $id)
    {
        $perm = $this->_findPermission('id',$id);

        if(!$perm) {
            throw new PermissionNotFoundException;
        }

        return $perm->update($attributes);
    }

    public function deleteRole(int $id)
    {
        $perm = $this->_findPermission('id',$id);

        if(!$perm) {
            throw new PermissionNotFoundException;
        }
        return $perm->delete();
    }

    public function createModule(array $attributes)
    {
        return Module::create($data);
    }

    public function findModule($value,$column = 'name')
    {
        return $this->_findModule($column,$value);
    }

    public function findModuleById(int $id)
    {
        return $this->_findModule('id',$id);
    }

    private function _findModule(string $column,$value)
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
