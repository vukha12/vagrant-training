document.addEventListener("DOMContentLoaded", function () {
  // Lấy chuỗi JSON từ local storage
  const userString = localStorage.getItem("currentUser");

  // Kiểm tra xem có dữ liệu người dùng không
  if (userString) {
    // Chuyển chuỗi JSON về lại đối tượng JavaScript
    const user = JSON.parse(userString);

    // Ví dụ: Hiển thị tên người dùng trên console để kiểm tra
    console.log("Thông tin người dùng đã lưu:", user);

    const welcomeMessage = document.getElementById("user-welcome");
  } else {
    window.location.href = "login.php";
  }
});
const logoutButton = document.getElementById("logout-button");
if (logoutButton) {
  logoutButton.addEventListener("click", function (event) {
    event.preventDefault(); // Ngăn hành vi mặc định của thẻ <a>
    localStorage.removeItem("currentUser");
    window.location.href = "login.php";
  });
}
