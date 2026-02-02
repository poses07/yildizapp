<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../db.php';

$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->booking_id) &&
    !empty($data->sender_type) &&
    !empty($data->message)
) {
    try {
        // 1. Get Booking Details to identify sender and receiver
        $stmt = $pdo->prepare("SELECT user_id, driver_id FROM bookings WHERE id = ?");
        $stmt->execute([$data->booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            echo json_encode(["success" => false, "message" => "Yolculuk bulunamadı."]);
            exit;
        }

        $userId = $booking['user_id'];
        $driverId = $booking['driver_id'];

        $senderId = null;
        $receiverId = null;

        if ($data->sender_type === 'user') {
            $senderId = $userId;
            $receiverId = $driverId; // Assuming driver_id in bookings maps to users table ID if drivers are users, BUT wait.
            // In my schema, drivers table is separate from users table.
            // So receiver_id for a driver might need to be treated carefully.
            // 'users' table is for customers. 'drivers' table is for drivers.
            // If I store sender_id/receiver_id, I need to know WHICH table they belong to.
            // But usually ID spaces might overlap if not careful.
            // However, the admin panel query joins `messages` with `users` table for both sender and receiver.
            // `LEFT JOIN users s ON m.sender_id = s.id`
            // `LEFT JOIN users r ON m.receiver_id = r.id`
            // This implies the admin panel expects BOTH sender and receiver to be in `users` table.
            // But my drivers are in `drivers` table!
        } else if ($data->sender_type === 'driver') {
            $senderId = $driverId; // This is from drivers table
            $receiverId = $userId; // This is from users table
        }
        
        // ISSUE:
        // If I use sender_id/receiver_id columns, I can't easily JOIN with a single table if senders/receivers can be from different tables (users vs drivers).
        // The Admin Panel code `messages.php` assumes `users` table for both. This is WRONG if drivers are in `drivers` table.
        
        // I need to fix `messages.php` to handle this.
        // It should check `sender_type`.
        // If `sender_type` is 'user', join `users` for sender, `drivers` for receiver (wait, no).
        // Actually, messages are between User and Driver.
        
        // Let's look at `messages.php` query again.
        /*
        SELECT m.*, 
               s.name as sender_name, s.phone as sender_phone,
               r.name as receiver_name, r.phone as receiver_phone
        FROM messages m
        LEFT JOIN users s ON m.sender_id = s.id
        LEFT JOIN users r ON m.receiver_id = r.id
        */
        
        // This is definitely expecting `users` table.
        // I need to change this logic in `messages.php` to be dynamic based on `sender_type`.
        
        // OR, I can just store `sender_id` and `receiver_id` in `booking_messages` and update `messages.php` to conditional join.
        // OR, even better, since I have `booking_id`, I can just join `bookings`, then join `users` and `drivers` to `bookings`.
        // `booking_messages` has `sender_type`.
        // If `sender_type` is 'user', then Sender is Booking.User, Receiver is Booking.Driver.
        // If `sender_type` is 'driver', then Sender is Booking.Driver, Receiver is Booking.User.
        
        // So I DON'T need `sender_id` and `receiver_id` columns in `booking_messages` at all!
        // I can derive everything from `booking_id` and `sender_type`.
        
        // So, I will revert `send_message.php` changes (actually I haven't applied them yet).
        // I will keep `send_message.php` as is (except maybe error handling).
        // I will update `messages.php` to use `booking_messages` and JOIN properly using `bookings` table.
        
        // Let's revert the plan to modify `send_message.php` logic.
        // I will just make sure it inserts into `booking_messages`. It already does.
        
        $stmt = $pdo->prepare("INSERT INTO booking_messages (booking_id, sender_type, message) VALUES (?, ?, ?)");
        $stmt->execute([
            $data->booking_id,
            $data->sender_type,
            $data->message
        ]);

        echo json_encode(["success" => true, "message" => "Mesaj gönderildi."]);

    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Veritabanı hatası: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Eksik veri."]);
}
?>
