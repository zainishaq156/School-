<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Us - DAR-UL-HUDA PUBLIC SCHOOL</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            background: linear-gradient(120deg, #e7f5ff 0%, #d1fffc 100%);
            min-height: 100vh;
        }
        main {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 35px 0 30px 0;
        }
        .aboutus-card {
            background: rgba(255,255,255,0.96);
            border-radius: 22px;
            box-shadow: 0 4px 28px #a5bce630, 0 1px 12px #7bf1c630;
            padding: 42px 38px 32px 38px;
            max-width: 870px;
            width: 97vw;
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            gap: 34px;
            margin-bottom: 18px;
            border-left: 7px solid #38dbc7;
            border-top: 4px solid #318cf6;
            position: relative;
            z-index: 2;
        }
        .aboutus-img-container {
            flex: 1 1 240px;
            min-width: 180px;
            max-width: 340px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            margin-right: 10px;
        }
        .aboutus-img {
            width: 100%;
            max-width: 340px;
            border-radius: 15px;
            box-shadow: 0 7px 24px #afd8ef32, 0 1px 7px #21e2af24;
            object-fit: cover;
            min-width: 160px;
            background: #e5f6fa;
        }
        .aboutus-title {
            font-family: 'Montserrat', Arial, sans-serif;
            font-size: 1.45em;
            color: #1849a6;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 15px;
            letter-spacing: .3px;
        }
        .aboutus-content {
            font-size: 1.09em;
            color: #42629d;
            line-height: 1.7;
            letter-spacing: 0.02em;
            margin-bottom: 0;
        }
        .aboutus-content strong {
            color: #17b287;
        }
        @media (max-width: 900px) {
            .aboutus-card {
                flex-direction: column;
                align-items: stretch;
                gap: 16px;
                padding: 22px 6vw 18px 6vw;
            }
            .aboutus-img-container {
                margin-bottom: 5px;
                max-width: 95vw;
                justify-content: flex-start;
            }
            .aboutus-img {
                max-width: 97vw;
                min-width: 100px;
            }
        }
        @media (max-width: 600px) {
            .aboutus-card {
                padding: 10px 3vw 10px 3vw;
                border-radius: 13px;
            }
            .aboutus-title {
                font-size: 1.07em;
            }
            .aboutus-content {
                font-size: .99em;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main>
        <section class="aboutus-card">
            <div class="aboutus-img-container">
                <img src="images/aboutus.jpg" alt="About Us Image" class="aboutus-img" loading="lazy">
            </div>
            <div style="flex:2;">
                <div class="aboutus-title">
                    <svg viewBox="0 0 24 24" fill="none" width="18" height="18" aria-hidden="true" style="margin-right:6px;">
                        <path d="M15.54 10.93L8.47 18a4 4 0 0 1-5.66-5.66l9.19-9.19a5 5 0 0 1 7.07 7.07l-9.19 9.19a2 2 0 1 1-2.83-2.83l8.48-8.48"
                            stroke="#437ef7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>About us</span>
                </div>
                <div class="aboutus-content">
                    <p>
                        Welcome to <strong>Dar ul Huda Public School</strong>, where knowledge, character, and values come together to shape the future of young minds. Established with a mission to provide quality education rooted in Islamic principles, we strive to nurture confident, disciplined, and responsible individuals.
                    </p>
                    <p>
                        At Dar ul Huda, we believe in education beyond textbooks. Our dedicated team of teachers focuses on academic excellence, moral development, and spiritual growth. We blend modern education with traditional Islamic teachings, ensuring our students grow into knowledgeable and ethical leaders of tomorrow.
                    </p>
                    <p>
                        We offer a safe, inclusive, and nurturing environment where each student is encouraged to explore their potential and excel in all aspects of life—intellectually, emotionally, socially, and spiritually.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>
