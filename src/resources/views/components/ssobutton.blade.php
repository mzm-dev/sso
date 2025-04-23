<style>
    .sso-button {
        display: flex;
        align-items: center;
        background-color: white;
        color: #0d3b66;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        border: 1px rgba(13, 59, 102, 0.3) solid;
        font-family: Arial, sans-serif;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        box-shadow: 1px 1px 4px rgba(0, 0, 0, 0.2);
        transition: background-color 0.3s, color 0.3s;
    }

    .sso-button svg {
        width: 32px;
        height: 32px;
        margin-right: 10px;
        fill: #0d3b66;
        transition: fill 0.3s;
    }

    .sso-text {
        display: flex;
        flex-direction: column;
        text-align: left;
    }

    .sso-text span {
        font-size: 14px;
        font-style: italic;
        font-weight: normal;
        opacity: 0.8;
    }

    .sso-button:hover {
        background-color: #0d3b66;
        color: white;
    }

    .sso-button:hover svg {
        fill: white;
    }

    .sso-button.btn-icon {
        padding: 8px;
        border-radius: 25%;
        width: 54px;
        height: 54px;
        justify-content: center;
    }

    .sso-button.btn-icon .sso-text {
        display: none;
    }

    .sso-button.btn-icon svg {
        margin: 0;
        width: 32px;
        height: 32px;
    }
</style>
<button class="sso-button btn-icon" title="Sistem iLogin (SSO)" type="button"
    onclick="window.location.href='{{ route('sso.auth') }}'">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shield-lock-fill"
        viewBox="0 0 16 16">
        <path fill-rule="evenodd"
            d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.465 9.99a11.8 11.8 0 0 0 2.517 2.453c.386.273.744.482 1.048.625.28.132.581.24.829.24s.548-.108.829-.24a7 7 0 0 0 1.048-.625 11.8 11.8 0 0 0 2.517-2.453c1.678-2.195 3.061-5.513 2.465-9.99a1.54 1.54 0 0 0-1.044-1.263 63 63 0 0 0-2.887-.87C9.843.266 8.69 0 8 0m0 5a1.5 1.5 0 0 1 .5 2.915l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99A1.5 1.5 0 0 1 8 5" />
    </svg>
    <div class="sso-text">
        Sistem iLogin
        <span>Single sign-on (SSO)</span>
    </div>
</button>
