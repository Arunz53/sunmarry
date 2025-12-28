<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Marriage Profile System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'header.php'; ?>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header text-center bg-danger text-white">
                        <h4>அணுகல் மறுக்கப்பட்டது</h4>
                    </div>
                    <div class="card-body text-center">
                        <p class="mb-4">உங்களுக்கு இந்த பக்கத்தை அணுக அனுமதி இல்லை.</p>
                        <a href="home.php" class="btn btn-primary">முகப்பு பக்கத்திற்குச் செல்க</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>