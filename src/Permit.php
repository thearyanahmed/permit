<?php

namespace Prophecy\Permit;

use Prophecy\Permit\Exceptions\{
    RoleNotFoundException,
    ModuleNotFoundException,
    PermissionNotFoundException
};
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

    public function deletePermission(int $id)
    {
        $perm = $this->_findPermission('id',$id);

        if(!$perm) {
            throw new PermissionNotFoundException;
        }
        return $perm->delete();
    }

    public function createModule(array $attributes)
    {
        return Module::create($attributes);
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

    public function findAbilitiesOf($role,$column = 'name')
    {
        $role = $this->_findRole($column,$role);

        if($role) {

            $modules = $role->load(['modules' => function($modules) use ($role) {
                $modules->with(['permissions' => function($perms) use ($role) {
                    $perms->where('role_id',$role->id);
                }])->groupBy('module_id');
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

            unset($role->modules,$role->created_at,$role->updated_at);

            return ['role' => $role, 'abilities' =>  $abilities] ;
        }

        return collect([]);
    }

    /**
     * @param $module
     * @param $permission
     * @param bool $findInSession
     * @return bool
     */
    public function authUserCan($permission,$module,bool $findInSession = true)
    {
        if(!$findInSession) {
            $roleId = session()->get($this->SESSION_ROLE_KEY)['id'];

            return $this->_findInDb($module,$permission,$roleId);
        }

        return $this->_findInSession($module,$permission);
    }

    public function can($permission,$module,$roleId)
    {
        return $this->_findInDb($module,$permission,$roleId);
    }

    private function _findInSession($module,$permission)
    {
        $data = session($this->SESSION_ABILITIES_KEY);

        if(isset($data[$module])) {
            if(isset($data[$module][$permission])) {
                return (bool) $data[$module][$permission];
            }
        }
        return false;
    }

    /**
     * @param $roleId
     * @param $module
     * @param $permission
     * @return bool
     * @throws ModuleNotFoundException
     * @throws PermissionNotFoundException
     */
    private function _findInDb($module, $permission,$roleId)
    {

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

    public function setAuthUserAbilities(int $roleId)
    {
        if(!auth()->check()) return false;

        $data = $this->findAbilitiesOf($roleId,'id');

        if(empty($data)) return false;


        if(empty($data)) return false;

        $roleInfo = [
            'id' => $data['role']->id,
            'name' => $data['role']->name
        ];

        session()->put($this->SESSION_ROLE_KEY,$roleInfo);
        session()->put($this->SESSION_ABILITIES_KEY,$data['abilities']);

        return true;
    }

    public function attachAbilities($roleId,$moduleId,$permissions)
    {
        if(!is_array($permissions)) {
            $permissions = [$permissions];
        }

        $abilities = Ability::where('role_id',$roleId)->where('module_id',$moduleId)->get();

        $makePermissionsWith = $permissions;

        if(count($abilities) > 0) {

            $existingPermissions = [];

            foreach($abilities as $key => $ability) {
                array_push($existingPermissions, $ability->permission_id);
            }

            $makePermissionsWith = array_diff($permissions,$existingPermissions);

            if(count($makePermissionsWith) === 0) return false;
        }

        $data = $this->_makeAbilitiesArray($roleId,$moduleId,$makePermissionsWith);

        return Ability::insert($data);
    }

    private function _makeAbilitiesArray(int $roleId,int $moduleId,array $permissions)
    {
        $array = [];

        foreach($permissions as $perm) {
            $array[] = [
                'role_id'       => $roleId,
                'module_id'     => $moduleId,
                'permission_id' => $perm
            ];
        }

        return $array;
    }

    public function detachAbilities($roleId,$moduleId,$permissions)
    {
        if(!is_array($permissions)) {
            $permissions = [$permissions];
        }
        return (bool) Ability::where('role_id',$roleId)->where('module_id',$moduleId)->whereIn('permission_id',$permissions)->delete();
    }
}
