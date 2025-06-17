<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Document</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        h1 {
            margin-bottom: 30px;
            font-size: 24px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        input[type="file"] {
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
            box-sizing: border-box;
        }
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            margin: 5px 0;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .btn-danger {
            background-color: #f44336;
        }
        .btn-danger:hover {
            background-color: #e53935;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Document</h1>
        <form id="uploadForm" action="upload_process.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="fileToUpload">Select document to upload (PDF, Word, Images):</label>
                <input type="file" name="fileToUpload" id="fileToUpload" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
            </div>
            <div class="form-group">
                <input type="submit" value="Upload Document" name="submit" class="btn">
                <button type="button" id="removeButton" class="btn btn-danger hidden">Remove File</button>
                <a href="profile.php" class="btn">Back to User Page</a>
            </div>
        </form>
    </div>

    <script>
        const fileInput = document.getElementById('fileToUpload');
        const removeButton = document.getElementById('removeButton');

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                removeButton.classList.remove('hidden');
            } else {
                removeButton.classList.add('hidden');
            }
        });

        removeButton.addEventListener('click', () => {
            fileInput.value = '';
            removeButton.classList.add('hidden');
        });
    </script>
</body>
</html>
