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

  $sql = "SELECT $select FROM complaints";
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
  $sql = "INSERT INTO complaints (complaintId, tenantId, roomId, description, status, createdAt) VALUES (:complaintId, :tenantId, :roomId, :description, :status, :createdAt)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['complaintId' => $input['complaintId'], 'tenantId' => $input['tenantId'], 'roomId' => $input['roomId'], 'description' => $input['description'], 'status' => $input['status'], 'createdAt' => $input['createdAt']]);
  echo json_encode(['message' => 'Complaint added succesfully']);
}

function handlePut($pdo, $input)
{
  // $sql = "UPDATE complaints SET tenantId=:tenantId, roomId=:roomId, description=:description, status=:status, createdAt=:createdAt WHERE complaintId=:complaintId";
  // $stmt = $pdo->prepare($sql);
  // $stmt->execute(['complaintId' => $input['complaintId'], 'tenantId' => $input['tenantId'], 'roomId' => $input['roomId'], 'description' => $input['description'], 'status' => $input['status'], 'createdAt' => $input['createdAt']]);
  // echo json_encode(['message' => 'Complaint updated succesfully']);
  if (!isset($input['complaintId'])) {
    echo json_encode(['message' => 'complaintId is required']);
    return;
  }
  $cols = ['tenantId', 'roomId', 'description', 'status', 'createdAt'];
  $fields = [];
  $params = [];

  foreach ($cols as $col) {
    if (isset($input[$col])) {
      $fields[] = "$col = :$col";
      $params[":$col"] = $input[$col];
    }
  }

  if ($fields) {
    $sql = "UPDATE complaints SET " . implode(", ", $fields) . " WHERE complaintId = :complaintId";
    $params[":complaintId"] = $input['complaintId'];

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
      echo json_encode(["message" => "Complaint updated successfully"]);
    } else {
      echo json_encode(["error" => $stmt->errorInfo()]);
    }
  } else {
    echo json_encode(["error" => "No fields to update"]);
  }
}

function handleDelete($pdo, $input)
{
  $sql = "DELETE FROM complaints WHERE complaintId = :complaintId";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['complaintId' => $_GET['complaintId']]);
  echo json_encode(["message" => "Complaint deleted succesfully"]);
}
?>