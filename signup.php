<?php
session_start();
include ('include/header.php');
include ('include/navbar.php');
?>

<div class="section-background">
    <div class="container">
        <div class="signup-section">
            <div class="signup-form">
                <img src="images/logo.png" alt="Department of Agriculture">
                <div class="form-box">
                    <div class="button-box">
                        <button type="button" class="toggle-btn active" onclick="showForm('cso-rep')">CSO
                            Representative</button>
                        <button type="button" class="toggle-btn" onclick="showForm('cso-chair')">CSO Office</button>
                        <div id="btn"></div>
                    </div>
                    <?php
                    if (isset($_SESSION['message'])) {
                        echo "<p class='error-message'>" . $_SESSION['message'] . "</p>";
                        unset($_SESSION['message']);
                    }
                    ?>

                    <form action="signup_process.php" method="post" enctype="multipart/form-data">
                        <?php if (isset($_SESSION['status'])): ?>
                            <div class="status" style="color: red; font-weight: bold;">
                                <?php echo $_SESSION['status']; ?>
                            </div>
                            <?php unset($_SESSION['status']); ?>
                        <?php elseif (isset($_SESSION['success'])): ?>
                            <div class="success" style="color: green; font-weight: bold;">
                                <?php echo $_SESSION['success']; ?>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>
                        <input type="hidden" name="form_type" value="cso_representative">
                        <!-- CSO Representative Form -->
                        <div class="form-container active" id="cso-rep-form">
                            <div class="form-group">
                                <hr>
                                    <p style="text-align: left;">
                                            <b style="font-size: 19px;">Representative Information</b>
                                            <br>
                                            <i style="color: gray; font-size: 14px;">Note: Before proceeding, ensure that your CSO Office is registered.</i>
                                            <br>
                                        </p>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">   
                                    <label for="last_name">Last Name <span style="color:red; font-weight:bold;">*</span></label>
                                    <input type="text" id="last_name" name="last_name" placeholder="ex. Cruz" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="first_name">First Name <span style="color:red; font-weight:bold;">*</span></label>
                                    <input type="text" id="first_name" name="first_name" placeholder="ex. John"
                                        required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="middle_name">Middle Name <span style="color:red; font-weight:bold;">*</span></label>
                                    <input type="text" id="middle_name" name="middle_name" placeholder="ex. Batumbakal"
                                        required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="suffix">Suffix</label>
                                    <input type="text" id="suffix" name="suffix" placeholder="ex. Jr.">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="birthday">Date of Birth <span style="color:red; font-weight:bold;">*</span></label>
                                    <input type="date" id="birthday" name="birthday" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="birth_place">Place of Birth <span style="color:red; font-weight:bold;">*</span></label>
                                    <input type="text" id="birth_place" name="birth_place" placeholder="ex. Iloilo"
                                        required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="nationality">Nationality <span style="color:red; font-weight:bold;">*</span></label>
                                    <select id="nationality" name="nationality" required>
                                        <option value="" disabled selected>Select your nationality</option>
                                        <option value="Filipino">Filipino</option>
                                        <option value="American">American</option>
                                        <option value="Canadian">Canadian</option>
                                        <option value="British">British</option>
                                        <option value="Australian">Australian</option>
                                        <option value="Indian">Indian</option>
                                        <option value="Chinese">Chinese</option>
                                        <option value="Japanese">Japanese</option>
                                        <option value="German">German</option>
                                        <option value="French">French</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="religion">Religion <span style="color:red; font-weight:bold;">*</span></label>
                                    <select id="religion" name="religion" required>
                                        <option value="" disabled selected>Select your religion</option>
                                        <option value="Christianity">Christianity</option>
                                        <option value="Islam">Islam</option>
                                        <option value="Hinduism">Hinduism</option>
                                        <option value="Buddhism">Buddhism</option>
                                        <option value="Judaism">Judaism</option>
                                        <option value="Sikhism">Sikhism</option>
                                        <option value="Atheism">Atheism</option>
                                        <option value="Agnosticism">Agnosticism</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="sex">Sex at Birth <span style="color:red; font-weight:bold;">*</span></label>
                                    <select id="sex" name="sex" required>
                                        <option value="">- Select Sex -</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="civil_status">Civil Status <span style="color:red; font-weight:bold;">*</span></label>
                                    <select id="civil_status" name="civil_status" required>
                                        <option value="">- Select Civil Status -</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Legally Separated">Legally Separated</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="email">Email Address <span style="color:red; font-weight:bold;">*</span></label>
                                    <input type="email" id="email" name="email"
                                        placeholder="ex. johncruz@example.com" required>
                                </div>
                                <?php
                                include ('include/db_connect.php'); 
                                
                                $sql = "
                                SELECT c.cso_name, c.first_name, c.last_name, c.suffix
                                FROM cso_chairperson c
                                LEFT JOIN cso_representative r ON c.cso_name = r.cso_name
                                WHERE r.id IS NULL";
                                $result = mysqli_query($conn, $sql);

                                $csoChairpersons = [];

                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $full_name = $row['cso_name'] . ' - ' . $row['first_name'] . ' ' . $row['last_name'] . ' ' . ($row['suffix'] ?? '');
                                        $csoChairpersons[] = [
                                            'cso_name' => $row['cso_name'],
                                            'full_name' => $full_name
                                        ];
                                    }
                                } else {
                                    $csoChairpersons = []; 
                                }

                                mysqli_close($conn);
                                ?>

                                <div class="form-group col-md-6">
                                    <label for="cso_name">CSO <span style="color:red; font-weight:bold;">*</span></label>
                                    <select id="cso_name" name="cso_name" required>
                                        <option value="">Select CSO</option>
                                        <?php foreach ($csoChairpersons as $chairperson): ?>
                                            <option value="<?php echo htmlspecialchars($chairperson['cso_name']); ?>">
                                                <?php echo htmlspecialchars($chairperson['full_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>


                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="mobile_number">Mobile Number <span style="color:red; font-weight:bold;">*</span></label>
                                    <input type="text" id="mobile_number" name="mobile_number"
                                        placeholder="ex. 09123456789" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="telephone_number">Telephone Number</label>
                                    <input type="text" id="telephone_number" name="telephone_number"
                                        placeholder="ex. 123-4567">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="password">Password <span style="color:red; font-weight:bold;">*</span></label>
                                    <input type="password" id="password" name="password" placeholder="ex. password123"
                                        required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="confirm_password">Confirm Password <span style="color:red; font-weight:bold;">*</span></label>
                                    <input type="password" id="confirm_password" name="confirm_password"
                                        placeholder="ex. password123" required>
                                </div>
                                </div>
                                <!-- Government ID Section -->
                        <div class="form-group">
                            <hr>
                            <p style="text-align: left;">
                                <b style="font-size: 19px;">Verification Requirements</b>
                                <br>
                                <i style="color: gray; font-size: 14px;">Note: Please upload scanned copies of the requirements in pdf file format.</i>
                            </p>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="rep_id_type">Government ID Type <span style="color:red; font-weight:bold;">*</span></label>
                                <select id="rep_id_type" name="rep_id_type" required style="height: 45px;">
                                    <option value="" disabled selected>Select ID Type</option>
                                    <option value="Passport">Passport</option>
                                    <option value="Drivers">Driver's License</option>
                                    <option value="National">National ID</option>
                                    <option value="SSS">SSS ID / SSS UMID</option>
                                    <option value="PRC">PRC</option>
                                    <option value="GSIS">GSIS ID / GSIS UMID</option>
                                    <option value="OWWA">OWWA ID</option>
                                    <option value="DOLE">iDOLE Card</option>
                                    <option value="Voters">Voter's ID</option>
                                    <option value="PWD">PWD ID</option>
                                    <option value="SC">Senior Citizen ID</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group col-md-8">
                                <label for="rep_gov_id_file">Government ID <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="file" id="rep_gov_id_file" name="rep_gov_id_file" accept=".jpg, .jpeg, .png, .pdf" required>
                            </div>
                        </div>
                                <input type="hidden" name="status" class="form-control" value="Pending">
                                <button type="submit" name="signin_btn" class="btn-custom btn-block">Register</button>
                            <div class="redirect-message">
                                Have an account? <a href="login.php">Log in</a>
                            </div>
                        </div>
                    </div>

                </form>

                <!-- CSO Chairperson Form -->

                <form action="signup_process.php" method="post" enctype="multipart/form-data">
                    <?php if (isset($_SESSION['status'])): ?>
                        <div class="error"><?php echo $_SESSION['status']; ?></div>
                        <?php unset($_SESSION['status']); ?>
                    <?php elseif (isset($_SESSION['success'])): ?>
                        <div class="success"><?php echo $_SESSION['success']; ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    <input type="hidden" name="form_type" value="cso_chairperson">
                    <div class="form-container active" id="cso-chair-form">
                        <div class="form-group">
                            <hr>
                                <p style="text-align: left; position: relative;">
                                        <b style="font-size: 19px;">Chairperson / President Information</b>
                                    </p>
                            </div>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="last_name">Last Name <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="text" id="last_name" name="last_name" placeholder="ex. Aquino" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="first_name">First Name <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="text" id="first_name" name="first_name" placeholder="ex. Jane" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="middle_name">Middle Name <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="text" id="middle_name" name="middle_name" placeholder="ex. Legarda"
                                    required>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="suffix">Suffix</label>
                                <input type="text" id="suffix" name="suffix" placeholder="ex. Sr.">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="birthday">Date of Birth <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="date" id="birthday" name="birthday" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="birth_place">Place of Birth <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="text" id="birth_place" name="birth_place" placeholder="ex. Aklan" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="nationality">Nationality <span style="color:red; font-weight:bold;">*</span></label>
                                <select id="nationality" name="nationality" required>
                                    <option value="" disabled selected>Select your nationality</option>
                                    <option value="Filipino">Filipino</option>
                                    <option value="American">American</option>
                                    <option value="Canadian">Canadian</option>
                                    <option value="British">British</option>
                                    <option value="Australian">Australian</option>
                                    <option value="Indian">Indian</option>
                                    <option value="Chinese">Chinese</option>
                                    <option value="Japanese">Japanese</option>
                                    <option value="German">German</option>
                                    <option value="French">French</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="religion">Religion <span style="color:red; font-weight:bold;">*</span></label>
                                <select id="religion" name="religion" required>
                                    <option value="" disabled selected>Select your religion</option>
                                    <option value="Christianity">Christianity</option>
                                    <option value="Islam">Islam</option>
                                    <option value="Hinduism">Hinduism</option>
                                    <option value="Buddhism">Buddhism</option>
                                    <option value="Judaism">Judaism</option>
                                    <option value="Sikhism">Sikhism</option>
                                    <option value="Atheism">Atheism</option>
                                    <option value="Agnosticism">Agnosticism</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="sex">Sex at Birth <span style="color:red; font-weight:bold;">*</span></label>
                                <select id="sex" name="sex" required>
                                    <option value="">- Select Sex -</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="civil_status">Civil Status <span style="color:red; font-weight:bold;">*</span></label>
                                <select id="civil_status" name="civil_status" required>
                                    <option value="">- Select Civil Status -</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Legally Separated">Legally Separated</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="mobile_number">Mobile Number <span style="color:red; font-weight:bold;">*</span></label>
                                    <input type="text" id="mobile_number" name="mobile_number"
                                        placeholder="ex. 09123456789" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="telephone_number">Telephone Number</label>
                                    <input type="text" id="telephone_number" name="telephone_number"
                                        placeholder="ex. 123-4567">
                                </div>
                            </div>
                        <div class="form-group">
                            <hr>
                                <p style="text-align: left;">
                                    <b style="font-size: 19px;">Office Information</b>
                                </p>
                        </div>
                        <div class="form-group">
                            <label for="cso_name">Name <span style="color:red; font-weight:bold;">*</span></label>
                            <input type="text" id="cso_name" placeholder="123 CSO" name="cso_name" required>
                        </div>
                        <div class="form-group">
                            <label for="cso_address">Registered Address <span style="color:red; font-weight:bold;">*</span></label>
                            <input type="text" id="cso_address" name="cso_address" placeholder="ex. 123 Main St"
                                required>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="region">Region <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="text" id="region" name="region" value="Region VI" disabled>
                            </div>

                            <div class="form-group col-md-4">
                                <label for="province">Province <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="text" id="province" name="province" placeholder="ex. Iloilo" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="city">Municipality <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="text" id="city" name="city" placeholder="ex. Iloilo City" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="barangay">Barangay <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="text" id="barangay" name="barangay" placeholder="ex. Barangay 1" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="street">Street <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="text" id="street" name="street" placeholder="ex. Elm Street" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="zip_code">Zip Code <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="text" id="zip_code" name="zip_code" placeholder="ex. 5000" required>
                            </div>
                        </div>
                        
          <!-- Input Fields -->
          <div class="form-group">
                <label for="user_address">Address <span style="color:red; font-weight:bold;">*</span></label>
                <div class="input-group">
                    <input type="text" id="user_address" class="form-control" placeholder="Enter full address (e.g., 123 Main St, Iloilo City) then click on the 'Find Location' button" required>
                    <div class="input-group-append">
                        <button type="button" id="geocode_btn" class="btn btn-primary">Find Location</button>
                    </div>
                </div>
            </div>

            <!-- Map Display -->
            <div id="map"></div>

            <!-- Coordinate Fields -->
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="latitude">Latitude <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" id="latitude" name="latitude" class="form-control" placeholder="ex. 14.5995" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="longitude">Longitude <span style="color:red; font-weight:bold;">*</span></label>
                    <input type="text" id="longitude" name="longitude" class="form-control" placeholder="ex. 120.9842" required>
                </div>
            </div>

            <!-- Include Leaflet Library -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

            <!-- Map Styling -->
            <style>
                #map {
                    width: 100%; /* Full width */
                    height: 200px; /* Adjusted height for clarity */
                    margin-bottom: 20px;
                    border: 2px solid #ccc; /* Add border for cleaner appearance */
                    border-radius: 8px; /* Rounded corners for better aesthetics */
                }
            </style>

            <!-- JavaScript for Map -->
            <script>
                // Initialize Leaflet Map
                var map = L.map('map').setView([11.2, 122.5], 8); // Centered on Panay Island

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19, // Higher zoom level for better details
                    errorTileUrl: 'https://via.placeholder.com/256?text=No+Map'
                }).addTo(map);

                // Add a marker to the map
                var marker = L.marker([11.2, 122.5], { draggable: true }).addTo(map);

                // Update input fields when the marker is dragged
                marker.on('moveend', function (e) {
                    var latLng = marker.getLatLng();
                    document.getElementById('latitude').value = latLng.lat.toFixed(6);
                    document.getElementById('longitude').value = latLng.lng.toFixed(6);
                });

                // Geocode Address on Button Click
                document.getElementById('geocode_btn').addEventListener('click', function () {
                    var address = document.getElementById('user_address').value;

                    // Use Nominatim API for Geocoding
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.length > 0) {
                                var lat = parseFloat(data[0].lat);
                                var lon = parseFloat(data[0].lon);

                                // Update input fields
                                document.getElementById('latitude').value = lat.toFixed(6);
                                document.getElementById('longitude').value = lon.toFixed(6);

                                // Update map and marker position
                                map.setView([lat, lon], 15);
                                marker.setLatLng([lat, lon]);
                            } else {
                                alert('Address not found. Please try again.');
                            }
                        })
                        .catch(err => console.error('Error with Geocoding:', err));
                });

                // Ensure tiles are fully loaded
                map.on('tileerror', function (e) {
                    console.warn('Tile loading error:', e);
                });
            </script>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="office_telephone_number">Telephone Number <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="text" id="office_telephone_number" name="office_telephone_number"
                                    placeholder="ex. 123-4567" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="email">Email Address <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="email" id="email" name="email"
                                    placeholder="ex. janeaquino@example.com" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="password">Password <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="password" id="password" name="password" placeholder="ex. password123"
                                    required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="confirm_password">Confirm Password <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                    placeholder="ex. password123" required>
                            </div>
                        </div>

                        <!-- Government ID Section -->
                        <div class="form-group">
                            <hr>
                            <p style="text-align: left;">
                                <b style="font-size: 19px;">Verification Requirements</b>
                                <br>
                                <i style="color: gray; font-size: 14px;">Note: Please upload scanned copies of the requirements in pdf file format.</i>
                            </p>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="chair_id_type">Government ID Type <span style="color:red; font-weight:bold;">*</span></label>
                                <select id="chair_id_type" name="chair_id_type" required style="height: 45px;">
                                    <option value="" disabled selected>Select ID Type</option>
                                    <option value="Passport">Passport</option>
                                    <option value="Drivers">Driver's License</option>
                                    <option value="National">National ID</option>
                                    <option value="SSS">SSS ID / SSS UMID</option>
                                    <option value="PRC">PRC</option>
                                    <option value="GSIS">GSIS ID / GSIS UMID</option>
                                    <option value="OWWA">OWWA ID</option>
                                    <option value="DOLE">iDOLE Card</option>
                                    <option value="Voters">Voter's ID</option>
                                    <option value="PWD">PWD ID</option>
                                    <option value="SC">Senior Citizen ID</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group col-md-8">
                                <label for="chair_gov_id_file">Government ID <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="file" id="chair_gov_id_file" name="chair_gov_id_file" accept=".jpg, .jpeg, .png, .pdf" required>
                            </div>
                        </div>

                        <!-- Certification Section -->
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="chair_certificate_type">Certificate Type <span style="color:red; font-weight:bold;">*</span></label>
                                <select id="chair_certificate_type" name="chair_certificate_type" required style="height: 45px;">
                                    <option value="" disabled selected>Select Certificate Type</option>
                                    <option value="SEC">SEC</option>
                                    <option value="DOLE">DOLE - BRW</option>
                                    <option value="CDA">CDA</option>
                                </select>
                            </div>
                            <div class="form-group col-md-8">
                                <label for="chair_certification_file">Certification Document <span style="color:red; font-weight:bold;">*</span></label>
                                <input type="file" id="chair_certification_file" name="chair_certification_file" accept=".jpg, .jpeg, .png, .pdf" required>
                            </div>
                        </div>
                        <input type="hidden" name="verification" class="form-control" value="unverified">
                        <button type="submit" name="signin_btn2" class="btn-custom btn-block">Register</button>
                
                    </form>
                <div class="redirect-message">
                    Have an account? <a href="login.php">Log In</a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    function showForm(formType) {
        var csoRepForm = document.getElementById('cso-rep-form');
        var csoChairForm = document.getElementById('cso-chair-form');
        var csoRepBtn = document.querySelector('.toggle-btn:nth-child(1)');
        var csoChairBtn = document.querySelector('.toggle-btn:nth-child(2)');

        if (formType === 'cso-rep') {
            csoRepForm.classList.add('active');
            csoChairForm.classList.remove('active');
            csoRepBtn.classList.add('active');
            csoChairBtn.classList.remove('active');
        } else if (formType === 'cso-chair') {
            csoRepForm.classList.remove('active');
            csoChairForm.classList.add('active');
            csoRepBtn.classList.remove('active');
            csoChairBtn.classList.add('active');
        }

        var btn = document.getElementById('btn');
        btn.style.left = formType === 'cso-rep' ? '0px' : '230px';
    }
    showForm('cso-rep');
</script>

<?php
include ('include/script.php');
include ('include/footer.php');
?>