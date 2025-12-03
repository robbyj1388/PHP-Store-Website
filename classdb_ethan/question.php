<?php
if (isset($_POST["action"])) {
    $action = $_POST["action"];
    $q_id = $_POST['q_id'];

    switch ($action) {
        case "remove":
            echo "You clicked REMOVE for question " . $q_id;
            break;
        case "update":
            echo "You clicked UPDATE for question " . $q_id;
            break;
        default:
            echo "The action $action has no processing code yet";
            break;
    }

    exit();
}
?>

<head>
    <title>Questions</title>
</head>
<body>

    <div>
        <span>Q1: The pace of this course</span>
        <form style="display: inline-block;" method="post">
            <input type="hidden" name="q_id" value="1">
            <button type="submit" name="action" value="remove">Remove</button>
            <button type="submit" name="action" value="update">Update</button>
        </form>
    </div>

    <div>
    <span>Q2: The feedback from homework assignment grading</span>
        <form style="display: inline-block;" method="post">
            <input type="hidden" name="q_id" value="2">
            <button type="submit" name="action" value="remove">Remove</button>
            <button type="submit" name="action" value="update">Update</button>
        </form>
    </div>

    <div>
    <span>Q3: Anything you like about the teaching of this course?</span>
        <form style="display: inline-block;" method="post">
            <input type="hidden" name="q_id" value="3">
            <button type="submit" name="action" value="remove">Remove</button>
            <button type="submit" name="action" value="update">Update</button>
        </form>
    </div>

</body>
</html>
