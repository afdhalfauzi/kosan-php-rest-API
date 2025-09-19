<?php
require "connect.php";
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
  case 'GET':
    handleGet($pdo);
    break;
  case 'POST':
    handlePost($pdo, $input);
    break;
  case 'PUT':
    handlePut($pdo, $input);
    break;
  case 'DELETE':
    handleDelete($pdo, $input);
    break;
  default:
    echo json_encode(['message' => 'Invalid request method']);
    break;
}

function handleGet($pdo)
{
  $select = "*"; //default

  if (isset($_GET['select']) && !empty($_GET['select'])) {
    $rawFields = $_GET['select'];
    //filter
    if (preg_match('/^[a-zA-Z0-9_,]+$/', $rawFields)) {
      $select = $rawFields;
    }
  }

  $sql = "SELECT $select FROM bookings";
  $conditions = [];
  $params = [];

  foreach ($_GET as $key => $value) {
    if (in_array($key, ["table", "select"]))
      continue;
    $conditions[] = "$key = :$key";
    $params[$key] = $value;
  }

  if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
  }

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($result);
}

function handlePost($pdo, $input)
{
  $sql = "INSERT INTO bookings (bookingId, tenantId, roomId, startDate, endDate, status, createdAt) VALUES (:bookingId, :tenantId, :roomId, :startDate, :endDate, :status, :createdAt)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['bookingId' => $input['bookingId'], 'tenantId' => $input['tenantId'], 'roomId' => $input['roomId'], 'startDate' => $input['startDate'], 'endDate' => $input['endDate'], 'status' => $input['status'], 'createdAt' => $input['createdAt']]);
  echo json_encode(['message' => 'Booking added succesfully']);
}

function handlePut($pdo, $input)
{
  // $sql = "UPDATE bookings SET tenantId=:tenantId, roomId=:roomId, startDate=:startDate, endDate=:endDate, status=:status, createdAt=:createdAt WHERE bookingId=:bookingId";
  // $stmt = $pdo->prepare($sql);
  // $stmt->execute(['bookingId' => $input['bookingId'], 'tenantId' => $input['tenantId'], 'roomId' => $input['roomId'], 'startDate' => $input['startDate'], 'endDate' => $input['endDate'], 'status' => $input['status'], 'createdAt' => $input['createdAt']]);
  // echo json_encode(['message' => 'Booking updated succesfully']);
  if (!isset($input['bookingId'])) {
    echo json_encode(['message' => 'bookingId is required']);
    return;
  }
  $cols = ['tenantId', 'roomId', 'startDate', 'endDate', 'status', 'createdAt'];
  $fields = [];
  $params = [];

  foreach ($cols as $col) {
    if (isset($input[$col])) {
      $fields[] = "$col = :$col";
      $params[":$col"] = $input[$col];
    }
  }

  if ($fields) {
    $sql = "UPDATE bookings SET " . implode(", ", $fields) . " WHERE bookingId = :bookingId";
    $params[":bookingId"] = $input['bookingId'];

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
      echo json_encode(["message" => "Booking updated successfully"]);
    } else {
      echo json_encode(["error" => $stmt->errorInfo()]);
    }
  } else {
    echo json_encode(["error" => "No fields to update"]);
  }
}

function handleDelete($pdo, $input)
{
  $sql = "DELETE FROM bookings WHERE bookingId = :bookingId";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['bookingId' => $input['bookingId']]);
  echo json_encode(["message" => "Booking deleted succesfully"]);
}
?>