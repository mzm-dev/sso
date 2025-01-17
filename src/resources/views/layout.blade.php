<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Web Design Mastery | 404 Not Found Page</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap");

        :root {
            --primary-color: #f9b23c;
            --primary-color-dark: #f49a20;
            --text-dark: #333333;
            --text-light: #767268;
            --extra-light: #ffffff;
        }

        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        body {
            height: 100vh;
            padding-top: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--extra-light);
            font-family: "Roboto", sans-serif;
            text-align: center;
        }

        nav {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        nav .nav__logo a {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            text-decoration: none;
        }

        .container {
            max-width: 400px;
            padding: 2rem;
            margin: auto;
            display: grid;
            color: var(--text-dark);
        }

        .header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .header h1 {
            font-size: 4rem;
            font-weight: 900;
        }

        .container h3 {
            font-size: 2rem;
            font-weight: 900;
        }

        .container img {
            width: 100%;
            max-width: 300px;
            margin: auto;
        }

        .content button:hover {
            background-color: var(--primary-color-dark);
        }

        .container .loader {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: inline-block;
            position: relative;
            border: 3px solid;
            border-color: #FFF #FFF transparent transparent;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }

        .container .loader::after,
        .container .loader::before {
            content: '';
            box-sizing: border-box;
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            margin: auto;
            border: 3px solid;
            border-color: transparent transparent #FF3D00 #FF3D00;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            box-sizing: border-box;
            animation: rotationBack 0.5s linear infinite;
            transform-origin: center center;
        }

        .container .loader::before {
            width: 32px;
            height: 32px;
            border-color: #333 #333 transparent transparent;
            animation: rotation 1.5s linear infinite;
        }

        @keyframes rotation {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes rotationBack {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(-360deg);
            }
        }

        @media (min-width: 640px) {
            nav {
                max-width: 1000px;
            }

            .container {
                max-width: 1200px;
                /* grid-template-columns: repeat(2, 1fr); */
                gap: 2rem;
            }

            .container img {
                max-width: 550px;
                grid-area: 1/1/3/2;
            }

            .footer {
                max-width: 300px;
            }
        }

        @media (min-width: 1024px) {
            .container {
                column-gap: 5rem;
            }

            .header h1 {
                font-size: 3rem;
            }

            .header h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <span class="loader"></span>
            <h1>@yield('title')</h1>
            <h3>@yield('message')</h3>
        </div>
        <div class="content">
            <p> @yield('content')</p>
        </div>
    </div>

    @yield('script')
</body>

</html>
