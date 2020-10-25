<?php
class Role
{
    protected $permissions;

    protected function __construct() {
        $this->permissions = array();
    }

    public static function getRolePerms($role_id) {
        $role = new Role();
        $sql = "SELECT t2.permission_desc FROM role_permission as t1
                JOIN permissions as t2 ON t1.permission_id = t2.permission_id
                WHERE t1.role_id = :role_id";
        $sth = $GLOBALS["db"]->prepare($sql);
        $sth->execute(array(":role_id" => $role_id));

        while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $role->permissions[$row["permission_desc"]] = true;
        }
        return $role;
    }

    public function hasPerm($permission) {
        return isset($this->permissions[$permission]);
    }
}


class PrivilegedUser /*extends User*/
{
    private $roles;

    public function __construct()
    {
        /*parent::__construct();*/
    }

    public static function getByStaffId($id)
    {
        $sql = "SELECT * FROM staffs WHERE id = :id";
        $sth = $GLOBALS["db"]->prepare($sql);
        $sth->execute(array(":id" => $id));
        $result = $sth->fetchAll();

        if (!empty($result)) {
            $privUser = new PrivilegedUser();
            $privUser->id = $id;
            $privUser->login = $result[0]["login"];
            $privUser->password = $result[0]["password"];
            $privUser->initRoles();
            return $privUser;
        } else {
            return false;
        }
    }

    protected function initRoles()
    {
        $this->roles = array();
        $sql = "SELECT t1.role_id, t2.role_name FROM staff_role as t1
                JOIN roles as t2 ON t1.role_id = t2.role_id
                WHERE t1.staff_id = :staff_id";
        $sth = $GLOBALS["db"]->prepare($sql);
        $sth->execute(array(":staff_id" => $this->id));

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $this->roles[$row["role_name"]] = Role::getRolePerms($row["role_id"]);
        }
    }

    public function hasPrivilege($permission)
    {
        foreach ($this->roles as $role) {
            if ($role->hasPerm($permission)) {
                return true;
            }
        }
        return false;
    }
}