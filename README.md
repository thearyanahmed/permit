## Permit 

A package for module wise permission for roles.Coming with very basic usages as creating role,module,permissions and assign module wise permission 
on roles.

### Installation 
 You can install it with composer.Go to your terminal and run this command from your project root directory.

```
composer require prophecy/permit
```
Wait for a while, it will download all dependencies.

Now migrate using 
```
php artisan migrate
```
To publish the config file run
```
php artisan vendor:publish --provider="Prophecy\Permit\PermitServiceProvider"
```
It will generate a config file in your `config/` directory labeled `permit.php`.You can modify these as you want.

```
return [
    'session_role_key'      => '_user_role',
    'session_abilities_key' => '_user_abilities',
    'redirect_to'           => 'home'
];
```

You can store the authenticated user's role and abilities in the session. The `session_role_key` and `session_abilities_key`'s value will be the `session_key` for authenticated user's roles and ability list.

for example 
```
session('_user_abilities') // returns you an array of abilities the user can perform 
```

### API 

***The package excepts your application to have role associated with your user.This package support User to have 1 role (1 to 1 relation)***


After migrating you'll see 4 additional tables in your database.Being the following `roles`,`modules`,
`permissions` & `role_permissions`. `role_permissions` table is our `ability` table.

#### Usage 

Just import the Permit class using 

```
use Prophecy\Permit\Permit;

```

##### Creating role

```
$permit = new Permit;

$role = [
    'name' => 'admin'
];

$permit->createRole($role);
```

##### Creating module

```
$permit = new Permit;

$module = [
    'name' => 'user'
];

$permit->createModule($module);
```

##### Creating permission

```
$permit = new Permit;

$perm = [
    'name' => 'create'
];

$permit->createPermission($perm);
```



##### Creating multiple 

```
$permit = new Permit;

$perm = [
    ['name' => 'force_delete'],
    ['name' => 'download'],
    ['name' => 'export'],
    ['name' => 'test']
];

$permit->createMultiplePermission($perm);

```

Like that you also have `createMultipleRole`.

*Create multiple module is still not supported along with few other module related functions.Will be added in the next version.


##### Finding role

```
$role = $permit->findRole('user');
```

##### Finding module

```
$mod  = $permit->findModule('articles');
```

##### Finding permission

```
$perm  = $permit->findPermission('create');
```


#### Attaching abilities to role

```
$permit->attachAbilities($role->id,$mod->id,$permissionToGive);
```
The `attachAbilities` method takes 3 params. `role_id`,`module_id`,`permission_ids`. `permission_ids` can be a single id value or an array of ids (non-associated). 



for example 

```
//fetch role 
$role = $permit->findRole('author');

//fetch module
$mod  = $permit->findModule('articles');
        
//fetch permissions   
$availablePermissions = $permit->allPermissions(); 

//map it down to an array.Because 'allPermissions' methods returns a collection.
$permissionToGive = $availablePermissions->map(function($perm){ return $perm->id; })->take(3)->toArray();
      
$permit->attachAbilities($role->id,$mod->id,$permissionToGive);
```

#### Detaching abilities from role
```
$permissions = 1;
//or
$permissions = [1,2,3];
 
$permit->detachAbilities($role->id,$module->id,$permissions);
```     

#### Finding abilities of roles 

```
$permit->findAbilitiesOf($role->name);
```

`findAbilitiesOf` function also takes a second option string argument,which is the `column_name` to look for in the `roles` table.By default it is set to `name`. You can also use `id` by doing the following.


```
$permit->findAbilitiesOf($role->id,'id');
```



Will return an array.


```
[
  {
    "role": {
      "id": 5,
      "name": "admin"
    },
    "abilities": {
      "articles": {
        "create": false,
        "read": true,
        "edit": false,
        "force_delete": true,
        "download": true,
        "export": true
      },
      "payments": {
        "create": true,
        "read": true,
        "edit": true,
        "force_delete": true,
        "download": true,
        "export": true
      },
      "users": {
        "create": true,
        "read": true,
        "edit": true,
        "force_delete": true,
        "download": true,
        "export": true
      },
      "hr": {
        "create": true,
        "read": true,
        "edit": true,
        "force_delete": true,
        "download": true,
        "export": true
      }
    }
  }
]
```

Note:The response will return a true false value based on the permissions attached with role -> module -> permissions.If you have **create** and **edit** permission attached to **hr** module, the response will also return the remaining permissions but the value will be `false`.


#### Using can

```
$permit->can($perm,$mod,$role->id);

//or 

$permit->can('create','articles',$roleId); 
```

The method signature is `$permission`,`$module` followed by `$roleId`. Will return a `boolean`.

```
can($permission,$module,$roleId)
```

`$permission` and `$module` are Instance of the model objects `Prophecy\Permit\Permission` and `Prophecy\Permit\Module` respectively.They can also be strings.`$roleId` is integer for this version (will be changed in next releases).

This method has a helper `user_can` with the same signature.

#### Using authUserCan

The authUserCan will take the first argumenet as `permission-name` and second argument as `module-name`.Both as strings and will return `Boolean`.
```
authUserCan($permissionName,$moduleName);
authUserCan('force_delete','articles');
```

Note: `authUserCan` (helper and class method) both takes an optional 3rd boolean parameter. `$findInSession` and which is set to true by default.Meaning Permit will look for the ability in `session` which can be set by  `setAuthUserAbilities` (described below). If you set the value to false,Permit will make a query to database against that role.Which is an implementation of `can` method.And in this case, the `$roleId` will be taken from session.It will be changed in the next versions.


And like `can`, **authUserCan** also has a helper `auth_user_can` with the same signature.

#### Setting authenticated user's abilities in session

You can simply call `setAuthUserAbilities` with the `$roleId` as parameter.

```
$permit->setAuthUserAbilities(auth()->user()->role_id); // role_id is numeric.
```


#### Middleware

add the following line in  the `route` middlewares array of `App\Http\Kernel` 

```
'permit' => \Prophecy\Permit\Middlewares\Permit::class,
```
This route middleware takes two params,`$module`, and `$permission`. And uses the `auth_user_can` method.


So you can use this like so
```
Route::get('test',function(){
    return 'you have permission';
})->middleware('permit:articles,force_delete');
```

If the auth user doesn't have permission,then will be redirected to `named` route defined in the `config/permit.php` file's `redirect_to`.

This is for very specific purpose.Hopefully it will have more functionality in the future.
