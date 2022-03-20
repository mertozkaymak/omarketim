<?php
header("Content: text/html; charset=utf-8");
header("Access-Control-Allow-Origin: *");

if((!isset($_POST["action"]) || !is_numeric($_POST["action"])) && !isset($_POST["draw"])) {
    exit();
}

$db = new PDO("mysql:host=localhost;dbname=omarketim_entegra", "omarketim_entegra", "Desire1489+!", array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM
));

$action = $_POST["action"];

if(isset($_POST["draw"])) {
    $columns = ["id", "name", "sku", "category", "stock", "price", "quantity", "margin", "sellprice", "foodcard"];
    $params = $_POST;

    $draw = $params['draw'];
    $row = $params['start'];
    $rowperpage = $params['length'];
    $columnIndex = $params['order'][0]['column'];        
    $columnName = $columns[$columnIndex];
    $sortDirection = $params['order'][0]['dir'];
    $searchValue = $params['search']['value'];

    $where = "";

    if($searchValue != '') {
        $where = " WHERE LOWER(name) LIKE '%" . mb_strtolower($searchValue) . "%' OR LOWER(sku) LIKE '%" . mb_strtolower($searchValue) . "%' OR LOWER(category) LIKE '%" . str_replace("K: ","", $searchValue) . "%'";
        if($searchValue == "YKA") {
            $where .= " OR foodcard = 1";
        }
        else if($searchValue == "YKK") {
            $where .= " OR foodcard = 0";
        }
    }
    
    $stmt = $db->prepare("SELECT id FROM products");
    $stmt->execute();
    $result = $stmt->fetchAll();
    $totalRecords = count($result);

    $products = array();

    $stmt = $db->prepare("SELECT id, name, sku, category, stock, price, quantity, real_stock, margin, sellprice, foodcard FROM products" . $where . " ORDER BY " . $columnName .  " " . $sortDirection . " LIMIT " . $row . "," . $rowperpage);
    $stmt->execute();

    $result = $stmt->fetchAll();

    if($result) {
        foreach($result as $row) {
            foreach($row as $key => $column) {
                if($key == 1) {
                    $row[$key] = '<input class="pname" data-id="' . $row[0] . '" type="text" class="xlarge" value="' . $row[$key] . '">';
                }
                else if($key == 3) {
                    $row[$key] = "K: " . $row[$key];
                }
                else if($key == 4) {
                    $row[$key] = '<span class="pstock">' . $row[$key] . '</span>';
                }
                else if($key == 5) {
                    $row[$key] = '<span class="pprice" data-price="' . $row[$key] . '">' . number_format($row[$key],2,",",".") . ' ₺</span>';
                }
                else if($key == 6) {
                    $row[$key] = '<input class="pquantity" data-id="' . $row[0] . '" type="text" class="xlarge" value="' . $row[$key] . '">';
                }
                else if($key == 7) {
                    $row[$key] = '<span class="prealstock">' . $row[$key] . '</span>';
                }
                else if($key == 8) {
                    $row[$key] = '<select class="pmargin" data-id="' . $row[0] . '">';
                    for($i = 0; $i <= 200; $i++) {
                        $row[$key] .= '<option' . ($column == $i ? ' selected' : '') . '>' . $i . '</option>';
                    }
                    $row[$key] .= '</select>';
                }
                else if($key == 9) {
                    $row[$key] = '<span class="psellprice" data-sellprice="' . $row[$key] . '">' . number_format($row[$key],2,",",".") . ' ₺</span>';
                }
                else if($key == 10) {
                    $row[$key] = '<input class="pfoodcard" data-id="' . $row[0] . '" type="checkbox"' . ($column == 1 ? ' checked' : '') . '>';
                }
            }        
            array_shift($row);
            array_push($products, $row);
        }	
    }

    $stmt = $db->prepare("SELECT id FROM products".$where);
    $stmt->execute();

    $result = $stmt->fetchAll();
    $totalRecordwithFilter = count($result);
                    
    $response = array(
        "draw" => (int)$draw,
        "iTotalRecords" => $totalRecords,
        "iTotalDisplayRecords" => $totalRecordwithFilter,
        "data" => $products
    );

    echo json_encode($response);
}
else if($action == 1) {

    $product = json_decode($_POST["product"], true);
    
    $stmt = $db->prepare("UPDATE products SET sellprice = ?, foodcard = ?, quantity = ?, name = ?, margin = ?, real_stock = ? WHERE id = ?");
    $result = $stmt->execute([$product["sellprice"], $product["foodcard"], $product["quantity"], $product["name"], $product["margin"], $product["realstock"], $product["id"]]);

    if($result) {
        echo 1;
    }
    else echo 0;

}
else if($action == 2) {

    $products = json_decode($_POST["products"], true);
    $return = 1;
    
    foreach($products as $product) {

        $stmt = $db->prepare("UPDATE products SET sellprice = ?, foodcard = ?, quantity = ?, name = ?, margin = ?, real_stock = ? WHERE id = ?");
        $result = $stmt->execute([$product["sellprice"], $product["foodcard"], $product["quantity"], $product["name"], $product["margin"], $product["realstock"], $product["id"]]);

        if(!$result) {
            $return = 0;
            break;
        }

    }

    echo $return;

}

$db = null;
?>