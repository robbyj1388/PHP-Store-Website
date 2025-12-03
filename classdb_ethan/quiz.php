<?php
if (isset($_POST["submit"])) {

    echo "Your answers are: <br>";

    foreach (array_keys($_POST) as $x) {
        if ($x != 'submit') {
            echo $x . ":" . $_POST[$x] . "<br>";
        }
    }

    exit();
}
?>

<head>
    <title>Quiz</title>
</head>
<body>

    <form action="quiz.php" method="post">

    <fieldset>
    <legend>Q1: The pace of this course</legend>
    <div>
        <input type="radio" id="q1a" name="q1" value="A">
        <label for="q1a">A: is too fast</label>
    </div>
    <div>
        <input type="radio" id="q1b" name="q1" value="B">
        <label for="q1b">B: is too slow</label>
    </div>
    <div>
        <input type="radio" id="q1c" name="q1" value="C">
        <label for="q1c">C: is just right</label>
    </div>
    <div>
        <input type="radio" id="q1d" name="q1" value="D">
        <label for="q1d">D: I don't know</label>
    </div>
    </fieldset>

    <fieldset>
    <legend>Q2: The feedback from homework assignment grading</legend>
    <div>
        <input type="radio" id="q2a" name="q2" value="A">
        <label for="q2a">A: is too harsh</label>
    </div>
    <div>
        <input type="radio" id="q2b" name="q2" value="B">
        <label for="q2b">B: is about right</label>
    </div>
    <div>
        <input type="radio" id="q2c" name="q2" value="C">
        <label for="q2c">C: I don't know</label>
    </div>
    </fieldset>

    <div>
        <label for="q3">Q3: Anything you like about the teaching of this course?</label>
        <br>
        <textarea id="q3" name="q3" rows="4" cols="50"></textarea>
    </div>

    <br>
    <input type="submit" value="Submit" name="submit">

    </form>

</body>
</html>
