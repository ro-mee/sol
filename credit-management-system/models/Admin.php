<?php
class Admin {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            logAudit('LOGIN', "Admin {$admin['username']} logged in");
            return true;
        }
        return false;
    }
    
    public function logout() {
        logAudit('LOGOUT', "Admin {$_SESSION['admin_username']} logged out");
        session_destroy();
    }
    
    public function getCurrentAdmin() {
        if (isset($_SESSION['admin_id'])) {
            return [
                'id' => $_SESSION['admin_id'],
                'username' => $_SESSION['admin_username']
            ];
        }
        return null;
    }
}
?>
