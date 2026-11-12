</main>
<footer class="footer">
    <span>Clube das Patas · <?= ($_SESSION['user_role'] ?? '') === 'cliente' ? 'Cuidado para o seu pet' : 'Gestão veterinária' ?></span>
    <span>Seus dados são tratados com cuidado e segurança.</span>
</footer>
</body>
</html>
