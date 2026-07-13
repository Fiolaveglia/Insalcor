<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>INSALCOR Admin – Ingresar</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <div class="auth-page">
    <div class="auth-card">
      <div class="auth-icon"><i class="fa-regular fa-file-lines"></i></div>
      <h1>INSALCOR Admin</h1>
      <p class="subtitle">Panel de Administración</p>
      <div id="alert" class="alert alert-error hidden"></div>
      <form id="login-form">
        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" placeholder="tu@email.com" required autocomplete="username">
        </div>
        <div class="form-group">
          <label for="password">Contraseña</label>
          <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-primary btn-block" type="submit">Ingresar</button>
      </form>
      <p class="auth-footer">¿No tenés cuenta? <a href="register.php">Registrarse</a></p>
    </div>
  </div>
  <script src="../assets/js/admin/api.js"></script>
  <script>
    (async () => {
      const me = await AdminAPI.me();
      if (me.user) location.href = 'index.php';
    })();

    document.getElementById('login-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const alertEl = document.getElementById('alert');
      alertEl.classList.add('hidden');
      try {
        await AdminAPI.login({
          email: document.getElementById('email').value,
          password: document.getElementById('password').value,
        });
        location.href = 'index.php';
      } catch (err) {
        alertEl.textContent = err.message;
        alertEl.classList.remove('hidden');
      }
    });
  </script>
</body>
</html>
