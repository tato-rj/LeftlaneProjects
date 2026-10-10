<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer"><title>Reset your QuickReads password</title></head>
<body>
<main>
<h1>Reset your QuickReads password</h1>
<form id="reset">
<label>Email <input name="email" type="email" autocomplete="email" required value="{{ request('email') }}"></label>
<label>New password <input name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required></label>
<label>Confirm password <input name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" required></label>
<button type="submit">Reset password</button>
</form>
<p id="message" role="status"></p>
</main>
<script>
const form = document.getElementById('reset');
form.addEventListener('submit', async event => {
    event.preventDefault();
    const button = form.querySelector('button');
    button.disabled = true;
    const message = document.getElementById('message');
    try {
        const body = Object.fromEntries(new FormData(form));
        body.token = new URLSearchParams(location.search).get('token');
        const response = await fetch('/api/v2/auth/password/reset', {
            method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json'}, body: JSON.stringify(body)
        });
        const result = await response.json();
        message.textContent = response.ok ? 'Your password has been reset. Sign in again in QuickReads.'
            : Object.values(result.error.fields).flat().join(' ') || result.error.message;
        if (response.ok) form.hidden = true;
    } catch (error) {
        message.textContent = 'The request could not be completed. Please try again.';
    } finally {
        button.disabled = false;
    }
});
</script>
</body>
</html>
