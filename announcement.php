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

  $sql = "SELECT $select FROM announcements";
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
  $sql = "INSERT INTO announcements (announcementId, adminId, title, message, targetAudience, createdAt) VALUES (:announcementId, :adminId, :title, :message, :targetAudience, :createdAt)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['announcementId' => $input['announcementId'], 'adminId' => $input['adminId'], 'title' => $input['title'], 'message' => $input['message'], 'targetAudience' => $input['targetAudience'], 'createdAt' => $input['createdAt']]);
  echo json_encode(['message' => 'Announcement added succesfully']);
}

function handlePut($pdo, $input)
{
  // $sql = "UPDATE announcements SET adminId=:adminId, title=:title, message=:message, targetAudience=:targetAudience, createdAt=:createdAt WHERE announcementId=:announcementId";
  // $stmt = $pdo->prepare($sql);
  // $stmt->execute(['announcementId' => $input['announcementId'], 'adminId' => $input['adminId'], 'title' => $input['title'], 'message' => $input['message'], 'targetAudience' => $input['targetAudience'], 'createdAt' => $input['createdAt']]);
  // echo json_encode(['message' => 'Announcement updated succesfully']);
  if (!isset($input['announcementId'])) {
    echo json_encode(['message' => 'announcementId is required']);
    return;
  }
  $cols = ['adminId', 'title', 'message', 'targetAudience', 'createdAt'];
  $fields = [];
  $params = [];

  foreach ($cols as $col) {
    if (isset($input[$col])) {
      $fields[] = "$col = :$col";
      $params[":$col"] = $input[$col];
    }
  }

  if ($fields) {
    $sql = "UPDATE announcements SET " . implode(", ", $fields) . " WHERE announcementId = :announcementId";
    $params[":announcementId"] = $input['announcementId'];

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
      echo json_encode(["message" => "Admin updated successfully"]);
    } else {
      echo json_encode(["error" => $stmt->errorInfo()]);
    }
  } else {
    echo json_encode(["error" => "No fields to update"]);
  }
}

function handleDelete($pdo, $input)
{
  $sql = "DELETE FROM announcements WHERE announcementId = :announcementId";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['announcementId' => $_GET['announcementId']]);
  echo json_encode(["message" => "Announcement deleted succesfully"]);
}
?>