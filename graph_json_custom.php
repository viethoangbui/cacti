<?php
ob_start();

// $guest_account = true;
// $auth_json     = true;

// final class ProcessRrd
// {
//     private $list = [];
//     private $table;
//     private $rrdNameField;
//     private $graphNameField;
//     private $clNumber;

//     public function __construct(
//         string $table,
//         string $rrdNameField,
//         string $graphNameField,
//         string $clNumber
//     ) {
//         $this->table = $table;
//         $this->rrdNameField = $rrdNameField;
//         $this->graphNameField = $graphNameField;
//         $this->clNumber = $clNumber;
//         $this->process();
//     }

//     private function process()
//     {
//         $list = db_fetch_assoc_prepared(
//             'SELECT ?, ?
// 					FROM ?
// 					WHERE cl_number = ?',
//             [$this->graphNameField, $this->rrdNameField, $this->table, $this->clNumber]
//         );

//         $this->list = $list;
//     }

//     public function get()
//     {
//         return $this->list;
//     }

//     public function responseJson()
//     {
//         $data = $this->list ?? [];
//         header('Content-Type: application/json; charset=utf-8');
//         echo json_encode($data);
//     }
// }

$json = file_get_contents('php://input');
$request = json_decode($json, true);

// $objProcessRrd = new ProcessRrd(
//     $request['table'],
//     $request['rrd_name_field'],
//     $request['graph_name_field'],
//     $request['cl_number']
// );

// $objProcessRrd->responseJson();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($request);
