<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSS File -->
    <link href="{{ asset('css/font.css') }}" rel="stylesheet">

    <title>{{ $data['title'] ?? 'DeGeest Notification' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-family: 'Avenir-Book' !important;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #F4F5F5;
            overflow: hidden;
            /* border-radius: 8px; */
            /* box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); */
        }

        .header {
            background: linear-gradient(135deg, #0A0E0F, #0A0E0F);
            /* padding: 42px 85px; */
            text-align: left;
            position: relative;
            border-top: 6px solid #98012E;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            background-color: #0A0E0F;
            border-radius: 50px;
            width: 100%;
            max-height: 160px;
        }

        .logo img {
            width: 100%;
            height: auto;
            max-height: 160px;
        }

        .logo-text {
            font-size: 24px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 1px;
        }

        .est-text {
            font-size: 12px;
            color: #cccccc;
            margin-top: 2px;
        }

        .tagline {
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tagline::before {
            content: '';
            width: 4px;
            height: 30px;
            background-color: #98012E;
            border-radius: 2px;
        }

        .content {
            padding: 20px 40px 0 40px;
            background-color: #F4F5F5;
        }

        .content-inner {
            background: var(--White, #FFFFFF);
            box-shadow: 0px 4px 12px 0px #0000001A;
            padding: 21px 24px;
            text-align: center;
        }

        .content h1 {
            font-size: 24px;
            line-height: 140%;
            color: #333333;
            margin-bottom: 12px;
            font-weight: 800;
        }

        .content p {
            font-weight: 400;
            font-size: 16px;
            line-height: 140%;
            letter-spacing: 0%;
            color: #242D2E;
        }

        .content .font-14 {
            font-weight: 400;
            font-size: 14px;
            line-height: 140%;
            letter-spacing: 0%;
            color: #242D2E;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #98012E, #98012E) !important;
            color: #ffffff !important;
            padding: 11px 24px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 400;
            font-size: 16px;
            line-height: 140%;
            letter-spacing: 0%;
            margin: 25px 0 40px 0;
            transition: all 0.3s ease;
        }

        /* .cta-button:hover {
            background: #0A0E0F !important;
        } */

        .footer {
            padding: 25px 40px;
            text-align: center;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            width: 100%;
        }

        .copyright {
            color: #999999;
            font-size: 14px;
        }

        .footer-links {
            gap: 15px;
            display: flex;
            flex-direction: row;
        }

        .footer-links>div {
            gap: 8px;
            display: flex;
            flex-direction: row;
        }

        .footer-links a {
            color: #94948F;
            text-decoration: none;
            font-size: 14px;
            line-height: 140%;
            transition: color 0.3s ease;
            display: flex;
        }

        .footer-links a:hover {
            color: #98012E;
        }

        .btn-div {
            text-align: center;
            width: 100%;
            margin-bottom: 0;
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 640px) {
            body {
                padding: 10px;
            }

            .header {
                padding: 25px 20px;
            }

            .logo-section {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .content {
                padding: 25px 20px;
            }

            .content h1 {
                font-size: 24px;
            }

            .footer {
                padding: 20px;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
            }

            .footer-links {
                justify-content: center;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .content {
                background-color: #ffffff;
            }
        }
    </style>
</head>

<body>
    <table class="email-container" cellpadding="0" cellspacing="0">
        <tr>
            <!-- Header Section -->
            <td class="header">
                <table>
                    <tr>
                        <td class="logo-section">
                            <table>
                                <tr>
                                    <td class="logo">
                                        <img src="{{ asset('images/logo.jpg') }}" alt="">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <!-- Main Content -->
            <td class="content">
                <table>
                    <tr>
                        <td class="content-inner">
                            <!-- <h1>{{ $data['title'] ?? 'Notification from DeGeest' }}</h1> -->

                            <p style="margin-top: 0; margin-bottom: 16px;">Hello {{ $data['name'] ?? 'User' }},</p>
                            <p style="margin-top: 0; margin-bottom: 16px;">
                                A new asset has been added to the Sharelink {{ $data['nmeOfObject'] }} by {{$data['loginUser']}}.
                                 Please review it at your convenience.
                            </p>

                            <p class="btn-div"><a href="{{ $data['url'] }}" class="cta-button">View Details</a></p>

                            <p class="font-14 ">If the button above doesn't work, copy and paste this link into your
                                browser:
                            </p>
                            @if (!empty($data['url']))
                            <p class="font-14 "><a href="{{ $data['url'] }}">{{ $data['url'] }}</a>
                            </p>
                            @endif
                            <p>Thank you for using DeGeest!</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <!-- Footer -->
            <td class="footer">
                <table style="width: 100%;">
                    <tr>
                        <td class="">
                            <div class="footer-content">
                                <p class="copyright" width="30%">©{{ date('Y') }} DeGeest</p>

                                <!-- <div class="footer-links" width="70%"> -->

                                    <!-- <div style="display: flex; align-items: center; justify-content: center;">
                                        <a href="#">Privacy Policy</a>
                                        <span style="margin: 0 5px;">·</span>
                                        <a href="#">Terms of Service</a>
                                        <span style="margin: 0 5px;">·</span>
                                        <a href="#">Email Support</a>
                                    </div> -->
                                <!-- </div> -->
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
