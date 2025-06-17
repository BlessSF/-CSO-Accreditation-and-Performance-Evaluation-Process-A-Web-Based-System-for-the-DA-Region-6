<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            padding: 20px;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .form-section {
            margin-bottom: 20px;
        }
        .form-section h4 {
            margin-bottom: 20px;
            color: #333;
        }
        .form-group label {
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Profile Information</h2>
    <form action="profile_process.php" method="POST">
        <div class="form-section">
            <h4>Personal Information</h4>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="fullName">Full Name</label>
                    <input type="text" class="form-control" id="fullName" name="fullName" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="dateOfBirth">Date of Birth</label>
                    <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Sex at Birth</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="sex" id="male" value="Male" required>
                        <label class="form-check-label" for="male">Male</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="sex" id="female" value="Female" required>
                        <label class="form-check-label" for="female">Female</label>
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label for="sexualOrientation">Sexual Orientation</label>
                    <select class="form-control" id="sexualOrientation" name="sexualOrientation">
                        <option value="Straight">Straight</option>
                        <option value="Gay">Gay</option>
                        <option value="Lesbian">Lesbian</option>
                        <option value="Bisexual">Bisexual</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="civilStatus">Civil Status</label>
                    <select class="form-control" id="civilStatus" name="civilStatus">
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Divorced">Divorced</option>
                        <option value="Widowed">Widowed</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="emailAddress">Email Address</label>
                    <input type="email" class="form-control" id="emailAddress" name="emailAddress" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="mobileNumber">Mobile Number</label>
                    <input type="text" class="form-control" id="mobileNumber" name="mobileNumber" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="telephoneNumber">Telephone Number</label>
                    <input type="text" class="form-control" id="telephoneNumber" name="telephoneNumber">
                </div>
                <div class="form-group col-md-4">
                    <label for="disability">Disability</label>
                    <select class="form-control" id="disability" name="disability">
                        <option value="None">None</option>
                        <option value="Blind">Blind</option>
                        <option value="Deaf">Deaf</option>
                        <option value="Physical">Physical</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h4>Address</h4>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="region">Region</label>
                    <input type="text" class="form-control" id="region" name="region" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="province">Province</label>
                    <input type="text" class="form-control" id="province" name="province" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="city">Municipality/City</label>
                    <input type="text" class="form-control" id="city" name="city" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="barangay">Barangay</label>
                    <input type="text" class="form-control" id="barangay" name="barangay" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="zipCode">Zip Code</label>
                    <input type="text" class="form-control" id="zipCode" name="zipCode" required>
                </div>
                <div class="form-group col-md-12">
                    <label for="address">Street Address</label>
                    <input type="text" class="form-control" id="address" name="address" required>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h4>Department</h4>
            <div class="form-group">
                <label for="department">Department</label>
                <select class="form-control" id="department" name="department" required>
                    <option value="CSO">CSO</option>
                    <option value="IT">IT</option>
                    <option value="ECE">ECE</option>
                    <option value="EE">EE</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Save and Continue</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>
</html>
