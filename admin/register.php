<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>INSALCOR Admin – Registro</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <div class="auth-page">
    <div class="auth-card">
      <div class="auth-icon"><i class="fa-regular fa-file-lines"></i></div>
      <h1>INSALCOR Admin</h1>
      <p class="subtitle">Crear cuenta</p>
      <div id="alert" class="alert alert-error hidden"></div>
      <form id="register-form">
        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" placeholder="tu@email.com" required autocomplete="username">
        </div>
        <div class="form-group">
          <label for="password">Contraseña</label>
          <input id="password" name="password" type="password" required minlength="6" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label for="password_confirm">Confirmar contraseña</label>
          <input id="password_confirm" name="password_confirm" type="password" required minlength="6" autocomplete="new-password">
        </div>
        <button class="btn btn-primary btn-block" type="submit">Registrarse</button>
      </form>
      <p class="auth-footer">¿Ya tenés cuenta? <a href="login.php">Ingresar</a></p>
    </div>
  </div>
  <script src="../assets/js/admin/api.js"></script>
  <script>
    document.getElementById('register-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const alertEl = document.getElementById('alert');
      alertEl.classList.add('hidden');
      const password = document.getElementById('password').value;
      const password_confirm = document.getElementById('password_confirm').value;
      if (password !== password_confirm) {
        alertEl.textContent = 'Las contraseñas no coinciden';
        alertEl.classList.remove('hidden');
        return;
      }
      try {
        await AdminAPI.register({
          email: document.getElementById('email').value,
          password,
          password_confirm,
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
