<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSO Accreditation - Home</title>
    <link rel="icon" type="image/x-icon" href="images/logo.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background-color: rgb(1, 82, 51);
            margin: 0;
            padding: 5px 70px;
        }
        .navbar a {
            color: white;
            opacity: 100%;
            text-decoration: none;
        }
        .navbar a:hover {
            background-color: rgb(2, 77, 48);
            text-decoration: none;
        }
        .navbar-toggler {
            border: none;
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba(255, 255, 255, 0.5%29' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
        }
        .navbar-collapse {
            background-color: rgb(1, 82, 51);
        }
        .navbar-nav .nav-item {
            margin: 0;
            padding: 0px 8px;
        }
        .navbar-nav .nav-item .nav-link {
            color: white;
            text-decoration: none;
        }
        .navbar-nav .nav-item .nav-link:hover {
            background-color: rgb(2, 77, 48);
        }
        .dropdown-menu {
            background-color: rgb(1, 82, 51);
            border: none;
            border-radius: 0;
        }
        .dropdown-item {
            color: white;
            text-decoration: none;
        }
        .dropdown-item:hover {
            background-color: rgb(2, 77, 48);
            color: white;
        }
        .navbar-nav .dropdown-menu .dropdown-item:hover {
            background-color: rgb(2, 77, 48);
        }
        .dropdown-submenu {
            position: relative;
        }
        .dropdown-submenu .dropdown-menu {
            display: none;
            position: absolute;
            top: 0;
            left: 100%;
            margin-top: -1px;
        }
        .dropdown-submenu:hover .dropdown-menu {
            display: block;
        }
        .dropdown-menu-parent {
            display: none;
        }
        .dropdown:hover .dropdown-menu-parent {
            display: block;
        }
        .govph-box {
            box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.2);
            padding: 5px 20px;
            background-color: rgb(2, 95, 59);
        }
        .yellow-line {
            background-color: rgb(253, 199, 5);
            height: 7px;
            width: 100%;
            margin: 0;
            padding: 0;
        }
        .banner {
            position: relative;
            width: 100%;
            padding-bottom: 11.95%;
            overflow: hidden;
        }
        .banner img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .carousel-inner {
            position: relative;
        }
        .carousel-inner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            z-index: 1;
        }
        .carousel-item::after {
            content: '';
            position: absolute;
            top: 0;
            left: 10%;
            right: 10%;
            height: 100%;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            z-index: 2;
            opacity: 0;
            transition: opacity 0.5s ease-out;
        }
        .carousel-item.active::after {
            opacity: 1;
        }
        .carousel-item:nth-child(1)::after {
            background-image: url('images/carousel1.png');
        }
        .carousel-item:nth-child(2)::after {
            background-image: url('images/carousel2.jpg');
        }
        .carousel-item:nth-child(3)::after {
            background-image: url('images/carousel3.jpg');
        }
        .carousel-item:nth-child(4)::after {
            background-image: url('images/carousel4.jpg');
        }
        .carousel-item img {
            width: 100%;
            height: 605px;
            object-fit: cover;
        }
        .carousel-control-prev, .carousel-control-next {
            z-index: 3;
            width: 5%;
        }
        .carousel-control-prev {
            left: 20px;
        }
        .carousel-control-next {
            right: 20px;
        }
        .carousel-control-prev-icon, .carousel-control-next-icon {
            background-size: 150%, 150%;
        }
        .section-title {
            background-color: #f8f8f8;
            padding: 20px;
            text-align: center;
        }
        .section {
            padding: 20px 0;
        }
        .section-background {
            background-image: url('images/background.jpg');
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .section-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.88);
            z-index: 0;
        }
        .section-content {
            display: flex;
            gap: 20px;
            padding: 20px 13px;
            position: relative;
            z-index: 1;
            flex-wrap: wrap;
        }
        .section-left, .combined-card {
            position: relative;
            z-index: 1;
        }
        .section-left {
            display: flex;
            flex-direction: column;
            gap: 20px;
            flex: 1;
        }
        .section-item {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 2px solid rgb(253, 199, 5);
            overflow: hidden;
            padding: 25px;
            height: auto;
            position: relative;
            background: transparent;
        }
        .section-item div {
            flex: 1;
        }
        .section-item h3 {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 10px;
            color: rgb(1, 82, 51);
        }
        .section-item p {
            font-size: 1em;
            margin-bottom: 20px;
            color: #333;
        }
        .btn-custom {
            background-color: rgb(1, 82, 51);
            color: white;
            border: none;
            padding: 5px 10px;
            font-size: 0.875em;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            align-self: flex-end;
            margin-top: auto;
        }
        .btn-custom:hover {
            color: white;
        }
        .btn-outline {
            background-color: transparent;
            color: rgb(1, 82, 51);
            border: 2px solid rgb(1, 82, 51);
            padding: 4px 10px;
            font-size: 0.875em;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
        }
        .btn-outline:hover {
            color: rgb(1, 82, 51);
        }
        .combined-card {
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 0;
            height: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .combined-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .combined-card-content {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .combined-card-content h3 {
            font-size: 1.75em;
            font-weight: bold;
            margin-bottom: 10px;
            color: rgb(1, 82, 51);
        }
        .combined-card-content p {
            font-size: 1em;
            color: #333;
            margin-bottom: 20px;
        }
        .combined-card-buttons {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }
        .footer {
            background-color: white;
            color: white;
            padding: 0;
        }
        .footer-top {
            background: url('images/footer_background.jpg') repeat bottom;
            background-size: auto;
            background-attachment: fixed;
            padding: 52px 0;
            display: flex;
            align-items: flex-start;
        }
        .footer-logo {
            flex: 0 0 auto;
            padding-left: 20px;
            margin-right: 20px;
            padding-bottom: 85px;
            display: flex;
            align-items: flex-start;
        }
        .footer-logo img {
            height: 140px;
        }
        .footer-content {
            flex: 1 1 auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .footer-bottom {
            background-color: white;
            padding: 10px 0;
        }
        .footer-bottom p {
            color: black;
            margin: 0;
        }
        .footer-title {
            font-weight: bold;
            font-size: 1.5em;
            margin-bottom: 10px;
        }
        .footer-section {
            padding: 20px;
            text-align: left;
        }
        .footer-section ul {
            list-style-type: none;
            padding: 0;
        }
        .footer-section ul li {
            margin-bottom: 5px;
        }
        .footer-section ul li::before {
            content: '• ';
            color: white;
        }
        .footer-section ul li a {
            color: white;
            text-decoration: none;
        }
        .footer-section ul li a:hover {
            text-decoration: underline;
        }
        .tab-content-wrapper {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            flex: 1;
        }
        .nav-tabs .nav-link {
            color: rgb(1, 82, 51);
        }
        .nav-tabs .nav-link.active {
            color: rgb(1, 82, 51);
        }
        .tab-pane .section-item {
            border: none;
        }
        @media (max-width: 768px) {
            .section-item {
                padding: 15px;
            }
            .combined-card-content {
                padding: 15px;
            }
            .dropdown-menu {
                position: static;
                float: none;
            }
            .dropdown-submenu .dropdown-menu {
                margin-left: 0;
            }
            .dropdown-menu .dropdown-submenu:hover .dropdown-menu {
                display: block;
                position: relative;
            }
        }
        .wider-col-md-8 {
            flex: 0 0 70%;
            max-width: 70%;
        }

        /* Add this CSS to hide dropdown arrows */
        .dropdown-toggle::after {
            display: none !important;
        }

        /* CSS to show dropdown on hover */
        .navbar-nav .dropdown:hover > .dropdown-menu {
            display: block;
        }
        .dropdown-menu .dropdown-submenu:hover > .dropdown-menu {
            display: block;
        }

        /* Ensure submenus appear within viewport */
        .dropdown-menu-right {
            right: 0;
            left: auto;
        }
        .dropdown-menu-left {
            right: auto;
            left: 0;
        }

        @media (max-width: 768px) {
            .dropdown-menu {
                position: static;
                float: none;
            }
            .dropdown-submenu .dropdown-menu {
                left: auto;
                right: 0;
                margin-left: 0;
                margin-right: 0;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item"><a class="nav-link govph-box" href="https://www.gov.ph/">GOVPH</a></li>
                <li class="nav-item"><a class="nav-link" href="https://westernvisayas.da.gov.ph/">Home</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="aboutUsDropdown" role="button" aria-haspopup="true" aria-expanded="false">About Us</a>
                    <div class="dropdown-menu dropdown-menu-left" aria-labelledby="aboutUsDropdown">
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/history/">History</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/mandate-vision-and-mission/">Mandate, Vision and Mission</a>
                        <div class="dropdown-submenu">
                            <a class="dropdown-item dropdown-menu-parent dropdown-toggle" href="#">Organization</a>
                            <ul class="dropdown-menu dropdown-menu-left">
                                <li><a class="dropdown-item" href="https://westernvisayas.da.gov.ph/organizational-chart/">Organizational Chart</a></li>
                                <li><a class="dropdown-item" href="https://westernvisayas.da.gov.ph/directory/">Directory</a></li>
                                <li><a class="dropdown-item" href="https://westernvisayas.da.gov.ph/staff-offices/">Staff & Offices</a></li>
                            </ul>
                        </div>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/agricultural-performance/">Agricultural Performance</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/agricultural-statistics/">Agricultural Statistics</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/citizens-charter/">Citizen’s Charter</a>
                    </div>
                </li>
                <li class="nav-item"><a class="nav-link" href="https://westernvisayas.da.gov.ph/programs/">Programs</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="servicesDropdown" role="button" aria-haspopup="true" aria-expanded="false">Services</a>
                    <div class="dropdown-menu dropdown-menu-left" aria-labelledby="servicesDropdown">
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/administrative-and-finance-division/">Administrative and Finance Division</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/agribusiness-and-marketing-assistance-division/">Agribusiness and Marketing Assistance Division</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/field-operations-division/">Field Operations Division</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/integrated-laboratory-division/">Integrated Laboratory Division</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/planning-monitoring-and-evaluation-division/">Planning, Monitoring, and Evaluation Division</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/regional-agricultural-engineering-division/">Regional Agricultural Engineering Division</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/regional-agricultural-fisheries-information-section/">Regional Agricultural, Fisheries, Information Section</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/regional-crop-protection-center/">Regional Crop Protection Center</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/regulatory-division/">Regulatory Division</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/research-division/">Research Division</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="transparencyDropdown" role="button" aria-haspopup="true" aria-expanded="false">Transparency</a>
                    <div class="dropdown-menu dropdown-menu-left" aria-labelledby="transparencyDropdown">
                        <div class="dropdown-submenu">
                            <a class="dropdown-item dropdown-menu-parent dropdown-toggle" href="#">Bids and Awards</a>
                            <ul class="dropdown-menu dropdown-menu-left">
                                <li><a class="dropdown-item" href="https://westernvisayas.da.gov.ph/invitation-to-bid/">Invitation to Bid</a></li>
                                <li><a class="dropdown-item" href="https://westernvisayas.da.gov.ph/supplemental-bulletin/">Supplemental Bulletin</a></li>
                                <li><a class="dropdown-item" href="https://westernvisayas.da.gov.ph/notice-of-award-bac-resolution/">Notice of Award & BAC Resolution</a></li>
                                <li><a class="dropdown-item" href="https://drive.google.com/drive/folders/1KKAlDtDY7VmyLzhA89Z5h9PTKnC12Pie">Notice to Proceed & Contract</a></li>
                                <li><a class="dropdown-item" href="https://westernvisayas.da.gov.ph/request-for-quotation/">Request for Quotation</a></li>
                                <li><a class="dropdown-item" href="https://westernvisayas.da.gov.ph/procurement-monitoring-report/">Procurement Monitoring Report</a></li>
                            </ul>
                        </div>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/transparency-seal/">Transparency Seal</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/job-opportunities/">Job Opportunities</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/gender-and-development/">Gender and Development</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/advisory-on-accreditation-of-cso/">Advisory on Accreditation of CSO</a>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/pre-assessed-farm-for-organic-certification/">Pre-Assessed Farm for Organic Certification</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="eLibraryDropdown" role="button" aria-haspopup="true" aria-expanded="false">E-library</a>
                    <div class="dropdown-menu dropdown-menu-left" aria-labelledby="eLibraryDropdown">
                        <div class="dropdown-submenu">
                            <a class="dropdown-item dropdown-menu-parent dropdown-toggle" href="#">Media Resources</a>
                            <ul class="dropdown-menu dropdown-menu-left">
                                <li><a class="dropdown-item" href="https://westernvisayas.da.gov.ph/annual-report/">Annual Report</a></li>
                                <li><a class="dropdown-item" href="https://westernvisayas.da.gov.ph/techno-guide/">Techno Guide</a></li>
                                <li><a class="dropdown-item" href="https://westernvisayas.da.gov.ph/suitability-map/">Suitability Map</a></li>
                                <li><a class="dropdown-item" href="https://westernvisayas.da.gov.ph/publication/">Publication</a></li>
                                <li><a class="dropdown-item" href="https://westernvisayas.da.gov.ph/nafmip/">NAFMIP</a></li>
                                <li><a class="dropdown-item" href="https://westernvisayas.da.gov.ph/corporate-plan/">Corporate Plan</a></li>
                            </ul>
                        </div>
                        <a class="dropdown-item dropdown-menu-parent" href="https://westernvisayas.da.gov.ph/research-compendium/">Research Compendium</a>
                    </div>
                </li>
                <li class="nav-item"><a class="nav-link" href="https://westernvisayas.da.gov.ph/success-stories/">Success Stories</a></li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><a class="nav-link" href="login.php">Login / Register</a></li>
            </ul>
        </div>
    </nav>

    <div class="yellow-line"></div>

    <div class="banner">
        <img src="images/header.png" alt="Department of Agriculture Banner" id="headerImage">
    </div>

    <div class="yellow-line"></div>

    <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
        <ol class="carousel-indicators">
            <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
            <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
            <li data-target="#carouselExampleIndicators" data-slide-to="2"></li>
            <li data-target="#carouselExampleIndicators" data-slide-to="3"></li>
        </ol>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img class="d-block w-100" src="images/carousel1.png" alt="First slide">
            </div>
            <div class="carousel-item">
                <img class="d-block w-100" src="images/carousel2.jpg" alt="Second slide">
            </div>
            <div class="carousel-item">
                <img class="d-block w-100" src="images/carousel3.jpg" alt="Third slide">
            </div>
            <div class="carousel-item">
                <img class="d-block w-100" src="images/carousel4.jpg" alt="Fourth slide">
            </div>
        </div>
        <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </div>

    <div class="yellow-line"></div>

    <div class="section-background">
        <div class="container">
            <div class="row section-content">
                <div class="col-md-8 d-flex flex-column wider-col-md-8">
                    <div class="combined-card">
                        <img src="images/card_header.png" alt="Card Header Image">
                        <div class="combined-card-content">
                            <div>
                                <h3>Why Apply for Accreditation?</h3>
                                <p>
                                    Applying for accreditation as a Civil Society Organization (CSO) within the Department of Agriculture opens doors to a multitude of advantages. It's not just a bureaucratic process; it's a strategic move that can elevate the organization's status and impact in numerous ways. <br>
                                <br>Firstly, accreditation serves as a badge of honor, signaling the CSO's unwavering commitment to upholding high standards of operation and ethics. This commitment enhances the organization's credibility and trustworthiness among stakeholders, including farmers, donors, and partnering agencies. <br>
                                <br>Accredited CSOs also enjoy tangible benefits, such as increased access to resources, funding, and support networks. These resources are instrumental in expanding the CSO's projects and initiatives, allowing them to tackle agricultural challenges more effectively and on a larger scale. <br>
                                <br>Furthermore, accreditation paves the way for fruitful collaborations with government agencies and other partners. By establishing formal relationships, CSOs can leverage the expertise and resources of these entities, fostering innovation and advocacy for policies that benefit rural communities. <br>
                                <br>In essence, accreditation is more than just a certification; it's a strategic step towards enhancing the impact and sustainability of agricultural development initiatives. It positions CSOs as key players in the field, empowering them to drive positive change and contribute meaningfully to the advancement of agriculture and rural livelihoods.
                                </p>
                            </div>
                            <div class="combined-card-buttons">
                                <a href="signup.php" class="btn-custom">Apply for Accreditation</a>
                                <a href="#" class="btn-outline">Download Form</a>
                            </div>
                        </div>
                    </div>
                    <div class="tab-content-wrapper mt-3">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">Accreditation</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="false">Application Process</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact" role="tab" aria-controls="contact" aria-selected="false">AccreditHub Features</a>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                <div class="section-item mt-3">
                                    <div>
                                        <h3>Elevating Excellence: CSO Accreditation in the Department of Agriculture</h3>
                                        <p>
                                            Accreditation is a vital process that ensures government agencies, including the Department of Agriculture, adhere to high standards of excellence, accountability, and transparency in public service. This accreditation is crucial in enhancing the efficiency and effectiveness of agricultural programs and services. <br>
                                        <br>Accreditation brings several benefits to the Department of Agriculture. Firstly, it significantly improves service delivery by ensuring that employees are well-trained and equipped with the necessary skills to provide high-quality services to farmers and other stakeholders. Additionally, the accreditation process promotes accountability and transparency within the department, ensuring that resources are used efficiently and ethically. <br>
                                        <br>The process of CSO accreditation involves several key steps. The department undergoes a thorough assessment of its current practices, policies, and procedures. This assessment includes evaluating staff performance, resource management, and service delivery. Based on the assessment, targeted training and development programs are implemented to address identified gaps and enhance staff competencies. The department then adopts best practices and standards in public service, aligning all operations with the principles of good governance. Continuous monitoring, audits, and reviews are conducted to ensure compliance with accreditation standards and to identify areas for further improvement. <br>
                                        <br>The benefits of accreditation to the Department of Agriculture are numerous. Accredited practices streamline operations, reduce wastage, and improve overall efficiency in service delivery. Accreditation also optimizes resource management, ensuring that funds and materials are allocated appropriately and effectively. Moreover, recognition through accreditation boosts staff morale and motivation, fostering a culture of excellence and dedication. Stakeholders, including farmers and suppliers, are more likely to engage with and support an accredited department, knowing that it upholds high standards. <br>
                                        <br>Accreditation is not just a mark of quality but a commitment to continuous improvement and excellence in public service. By adhering to accreditation standards, the department can better serve the agricultural community, promote sustainable practices, and contribute to the overall development of the sector.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                                <div class="section-item mt-3">
                                    <div>
                                        <h3>Embark on Your Accreditation Journey: Streamlined Application Process</h3>
                                        <p>
                                            This platform is designed to streamline and enhance the accreditation process, offering a comprehensive and user-friendly solution for both CSOs and DA administrators. <br>
                                        <br>The new system is set to revolutionize the way CSOs engage with the accreditation process. It will allow organizations to verify document quality during accreditation, renewal, and maintenance phases. Administrators, on the other hand, will benefit from secure document archiving, ensuring that all verified documents are safely stored and easily accessible. <br>
                                        <br>One of the standout features of the system is its integration of performance monitoring and project management functionalities. This will enable the DA to oversee the performance of accredited CSOs effectively. Additionally, a unique feature will allow administrators to assess the overall performance of CSOs annually, ranking them based on their achievements and compliance. <br>
                                        <br>The user journey within this system is designed to be straightforward and efficient. It begins with CSOs registering their key information, such as the organization’s name, email, and address. Upon verification of the organization's uniqueness, an account will be created, marking the start of the accreditation application process. <br>
                                        <br>The system boasts a user-friendly interface for uploading, verifying, and archiving documents, aimed at improving collaboration within the DA. CSOs will have seamless access to important documents, including notices, advisories, downloadable files, and online forms tailored specifically for accreditation applications and annual report submissions. <br>
                                        <br>This innovative web-based system promises to enhance the efficiency and transparency of the accreditation process within the Department of Agriculture, providing significant benefits to CSOs and administrators alike. By leveraging technology, the DA is taking a significant step towards modernizing its operations and ensuring that the accreditation process is both effective and user-centric. <br>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                                <div class="section-item mt-3">
                                    <div>
                                        <h3>Empowering CSOs: Unveiling the Essential Features of Our Accreditation Platform</h3>
                                        <p>
                                        <h3 style="font-size: 16px;">User Registration</h3>
                                        The registration process is simple and efficient, allowing CSOs to quickly start their accreditation journey by providing key information such as the organization’s name, email, and address.
                                        <br> <br>
                                        <h3 style="font-size: 16px;">User-Friendly Interface for Document Management</h3>
                                        An intuitive interface allow CSOs to easily upload, verify, and archive documents, facilitating smooth and efficient document management.
                                        <br> <br>
                                        <h3 style="font-size: 16px;">Access to Important Documents</h3>
                                        CSOs have seamless access to essential documents, including notices, advisories, downloadable files, and online forms designed specifically for accreditation applications and annual report submissions.
                                        <br> <br>
                                        <h3 style="font-size: 16px;">Report and Financial Statement Submission</h3>
                                        Accredited CSOs may submit reports and financial statements, which were processed into key performance indicators (ROI, liquidity, solvency) and visualized for easy interpretation.
                                        <br> <br>
                                        <h3 style="font-size: 16px;">Compliance Enforcement</h3>
                                        The system ensure compliance by restricting access to renewal features for incomplete submissions and providing immediate notifications of non-compliance, guiding CSOs to meet all requirements.
                                        <br> <br>
                                        <h3 style="font-size: 16px;">Scheduling Tool for Appointments</h3>
                                        A built-in scheduling tool to help CSOs manage appointments effectively, ensuring timely submissions and interactions with the Department of Agriculture.
                                        <br> <br>
                                        <h3 style="font-size: 16px;">Integrated Project Management System</h3>
                                        Facilitates efficient project tracking, progress monitoring, and collaboration, enabling CSOs to manage their initiatives effectively and ensuring timely completion of projects.
                                        <br> <br>
                                        <h3 style="font-size: 16px;">Performance Monitoring and Annual Assessment</h3>
                                        CSOs benefit from integrated performance monitoring tools and annual assessments that ranked their performance, helping them understand their standing and areas for improvement.
                                        <br> <br>
                                        <h3 style="font-size: 16px;">Enhanced Operational Efficiency</h3>
                                        The platform streamlines the accreditation process, saving CSOs time and effort by reducing administrative burdens and improving overall operational efficiency.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 section-left">
                    <div class="section-item">
                        <div>
                            <h3>Public Advisories</h3>
                            <p>
                            <b>READ! DA-BAI Memorandom Circular No. 25</b> <br> 
                            Waiving of Avian Influenza Testing per shipment of live poultry, other bird species, eggs and 
                            poultry products and by-products except live ducks coming from Region VI (Western Visayas) for 
                            local movement purposes until July 30, 2024 only.
                            </p>
                        </div>
                        <a href="https://westernvisayas.da.gov.ph/da-orients-tubungan-vegetable-producers-association-members-on-participatory-guarantee-system/?fbclid=IwZXh0bgNhZW0CMTAAAR3t3wy220E6D204oiOxRkUKh6vK7ptGYnVuyYwlv-ZCHwOt8a1Giq64iJo_aem_AfrMNMgBkdDO3VMXObriyU5jve-Y8ZAkVxouMv-pXKYVIaPPffL1KyUeE9CHH1qOSymYHDCI9dJ6UKPxQZ2Kj7um" class="btn-custom">View >> </a>
                    </div>
                    <div class="section-item">
                        <div>
                            <h3>CSO Accreditation Status</h3>
                            <p>
                            CSOs in Region 6 are pivotal forces in community development and rural welfare advocacy. 
                            Accreditation from the DA solidifies their adherence to established standards and guidelines, 
                            granting them increased legitimacy and credibility. This recognition opens doors to resources, partnerships, 
                            and government support, validating their contributions and fostering collaboration. With accredited status, 
                            these CSOs are empowered to advocate for farmers' needs, drive community-driven initiatives, and propel 
                            socio-economic progress in the region.
                            </p>
                        </div>
                        <a href="https://drive.google.com/file/d/1g1hee_s2WnIUZt8SVQW41L0ZyAExYhJU/view" class="btn-custom">View >> </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="yellow-line"></div>

    <footer class="footer">
        <div class="footer-top">
            <div class="footer-content container d-flex align-items-center">
                <div class="footer-logo">
                    <img src="images/govseal.png" alt="Logo" class="img-fluid">
                </div>
                <div class="row w-100 align-items-start">
                    <div class="col-md-4 footer-section">
                        <div class="footer-title">Republic of the Philippines</div>
                        <p>All content is in the public domain unless otherwise stated.</p>
                        <p>Privacy Policy</p>
                    </div>
                    <div class="col-md-4 footer-section">
                        <div class="footer-title">About GOVPH</div>
                        <p>Learn more about the Philippine government, its structure, how government works and the people behind it.</p>
                        <ul>
                            <li><a href="https://www.gov.ph/">Official Gazette</a></li>
                            <li><a href="https://data.gov.ph/index/home">Open Data Portal</a></li>
                        </ul>
                    </div>
                    <div class="col-md-4 footer-section">
                        <div class="footer-title">Government Links</div>
                        <ul>
                            <li><a href="https://president.gov.ph/">Office of the President</a></li>
                            <li><a href="https://www.ovp.gov.ph/">Office of the Vice President</a></li>
                            <li><a href="https://legacy.senate.gov.ph/">Senate of the Philippines</a></li>
                            <li><a href="https://www.congress.gov.ph/">House of Representatives</a></li>
                            <li><a href="https://sc.judiciary.gov.ph/">Supreme Court</a></li>
                            <li><a href="https://ca.judiciary.gov.ph/">Court of Appeals</a></li>
                            <li><a href="https://sb.judiciary.gov.ph/">Sandiganbayan</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="text-center">© 2024 DA Western Visayas. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.dropdown-submenu a.dropdown-toggle').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).next('.dropdown-menu').toggle();
            });

            $('.dropdown-menu a.dropdown-menu-parent').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).next('.dropdown-menu').toggle();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.navbar').length) {
                    $('.dropdown-menu').hide();
                }
            });
        });
    </script>
</body>
</html>