<?php
    require_once('controllers/SelectionController.php');

    $value = $_GET['value'];
    $type = $_GET['type'];

    $controller = new SelectionController();
    $options = $controller->getOptions($type, $value);
    
    echo json_encode($options);
