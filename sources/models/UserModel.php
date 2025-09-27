<?php

require_once 'BaseModel.php';

class UserModel extends BaseModel
{

    public function findUserById($id)
    {
        $sql = 'SELECT * FROM users WHERE id = ' . $id;
        $user = $this->select($sql);

        return $user;
    }

    public function findUser($keyword)
    {
        $sql = 'SELECT * FROM users WHERE user_name LIKE %' . $keyword . '%' . ' OR user_email LIKE %' . $keyword . '%';
        $user = $this->select($sql);

        return $user;
    }

    /**
     * Authentication user
     * @param $userName
     * @param $password
     * @return array
     */
    // chưa sửa login
    // public function auth($userName, $password) {
    //     $md5Password = md5($password);
    //     $sql = 'SELECT * FROM users WHERE name = "' . $userName . '" AND password = "'.$md5Password.'"';

    //     $user = $this->select($sql);
    //     return $user;
    // }
    // đã chặn phần login
    // UserModel.php (Mã nguồn đã sửa - AN TOÀN)
    public function auth($userName, $password)
    {
        $md5Password = md5($password);

        // 1. Sử dụng Prepared Statement với placeholders (?)
        // Tên và mật khẩu được mã hóa sẽ được thay thế an toàn
        $sql = 'SELECT * FROM users WHERE name = ? AND password = ?';

        // 2. Chuẩn bị (Prepare) câu lệnh SQL
        $stmt = self::$_connection->prepare($sql);

        // KIỂM TRA LỖI: Luôn kiểm tra xem lệnh prepare có thành công không
        if (!$stmt) {
            die('Lỗi prepare SQL: ' . self::$_connection->error);
        }

        // 3. Liên kết tham số (Bind Parameters)
        // "ss" chỉ định rằng cả hai tham số đều là chuỗi (string)
        $stmt->bind_param("ss", $userName, $md5Password);

        // 4. Thực thi (Execute)
        $stmt->execute();

        // 5. Lấy kết quả
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        // 6. Đóng Statement
        $stmt->close();

        return $rows;
    }

    /**
     * Delete user by id
     * @param $id
     * @return mixed
     */
    public function deleteUserById($id)
    {
        $sql = 'DELETE FROM users WHERE id = ' . $id;
        return $this->delete($sql);
    }

    /**
     * Update user
     * @param $input
     * @return mixed
     */
    public function updateUser($input)
    {
        $sql = 'UPDATE users SET 
                 name = "' . mysqli_real_escape_string(self::$_connection, $input['name']) . '", 
                 password="' . md5($input['password']) . '"
                WHERE id = ' . $input['id'];

        $user = $this->update($sql);

        return $user;
    }

    /**
     * Insert user
     * @param $input
     * @return mixed
     */
    // public function insertUser($input) {
    //     $sql = "INSERT INTO `app_web1`.`users` (`name`, `password`) VALUES (" .
    //             "'" . $input['name'] . "', '".md5($input['password'])."')";

    //     $user = $this->insert($sql);

    //     return $user;
    // }


    //đã sửa
    public function insertUser($input)
    {
        $name = isset($input['name']) ? trim($input['name']) : '';
        $fullname = isset($input['fullname']) ? trim($input['fullname']) : '';
        $email = isset($input['email']) ? trim($input['email']) : '';
        $type = isset($input['type']) ? trim($input['type']) : '';
        $password = isset($input['password']) ? $input['password'] : '';

        if ($name === '' || $password === '') return false;

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (`name`, `fullname`, `email`, `type`, `password`) VALUES (?, ?, ?, ?, ?)";
        $stmt = self::$_connection->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed insertUser: " . self::$_connection->error);
            return false;
        }
        $stmt->bind_param('sssss', $name, $fullname, $email, $type, $hash);
        $ok = $stmt->execute();
        if ($ok) {
            $insertId = $stmt->insert_id;
            $stmt->close();
            return $insertId;
        } else {
            error_log("Execute failed insertUser: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }


    /**
     * Search users
     * @param array $params
     * @return array
     */

    // Chưa Sửa Tấn Công SQL Injection  
    // public function getUsers($params = []) {
    //     //Keyword
    //     if (!empty($params['keyword'])) {
    //     $sql = 'SELECT * FROM users WHERE name LIKE "%' . $params['keyword'] .'%"';

    //     // 1. Thực thi Multi-Query (Đã xóa bảng thành công)
    //     self::$_connection->multi_query($sql); // ẩn dòng này để chặn xóa bảng %" ; DROP TABLE banks; --

    //     // 2. XỬ LÝ KẾT QUẢ ĐỂ TRÁNH LỖI "COMMANDS OUT OF SYNC"
    //     // Dùng vòng lặp để xử lý (bỏ qua) tất cả các kết quả từ Multi-Query
    //     do {
    //         if ($result = self::$_connection->store_result()) {
    //             $result->free();
    //         }
    //     } while (self::$_connection->more_results() && self::$_connection->next_result());

    //     // 3. Đoạn code này bây giờ sẽ chạy lại mà không bị lỗi
    //     $users = $this->query($sql); 
    //     } else {
    //         $sql = 'SELECT * FROM users';
    //         $users = $this->select($sql);
    //     }

    //     return $users;
    // }


    // Dã Sửa Tấn Công SQL Injection  
    // UserModel.php (Sửa chữa KHẨN CẤP)

    public function getUsers($params = [])
    {
        //Keyword
        if (!empty($params['keyword'])) {

            // 1. Chuẩn bị giá trị keyword cho LIKE
            $keyword = '%' . $params['keyword'] . '%';

            // 2. Sử dụng Prepared Statements với placeholder (?)
            $sql = 'SELECT * FROM users WHERE name LIKE ?';

            // KHÔNG BAO GIỜ DÙNG multi_query() VỚI DỮ LIỆU NGƯỜI DÙNG.
            // Thay thế bằng lệnh Prepare và Execute của mysqli.
            $stmt = self::$_connection->prepare($sql);

            // Liên kết tham số (s: string)
            $stmt->bind_param("s", $keyword);

            // Thực thi
            $stmt->execute();

            // Lấy kết quả
            $result = $stmt->get_result();
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        } else {
            $sql = 'SELECT * FROM users';
            // Vẫn nên chuyển hàm select này sang Prepared Statements sau này
            $users = $this->select($sql);
        }

        return $users;
    }
}
