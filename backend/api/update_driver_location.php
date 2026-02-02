<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../db.php';

$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->driver_id) &&
    isset($data->latitude) &&
    isset($data->longitude)
) {
    try {
        $sql = "UPDATE drivers SET latitude = :lat, longitude = :lng WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':lat', $data->latitude);
        $stmt->bindParam(':lng', $data->longitude);
        $stmt->bindParam(':id', $data->driver_id);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Location updated."]);
        } else {
            echo json_encode(["success" => false, "message" => "Update failed."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Missing data."]);
}
?>
