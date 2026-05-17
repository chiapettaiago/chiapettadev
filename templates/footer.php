<?php
require_once __DIR__ . '/../admin/modules/SiteItem.php';

$footerProjects = SiteItem::getPublishedBySection('project', 3);

if (!function_exists('footer_link_target')) {
    function footer_link_target($url) {
        return preg_match('/^https?:\/\//i', $url ?? '') ? ' target="_blank" rel="noopener"' : '';
    }
}
?>
    <footer>
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 3rem; margin-bottom: 3rem;">
                <!-- Seção Sobre -->
                <div>
                    <h5 style="color: var(--accent); margin-bottom: 1rem; font-weight: 700;">ChiapettaDev</h5>
                    <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 1.5rem;">
                        Desenvolvedor Full Stack apaixonado por tecnologia, inovação e por transformar ideias em soluções eficientes e intuitivas.
                    </p>
                </div>

                <!-- Seção Links Rápidos -->
                <div>
                    <h5 style="color: var(--accent); margin-bottom: 1rem; font-weight: 700;">Links Rápidos</h5>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;"><a href="#sobre" style="color: var(--text-muted); text-decoration: none; transition: color 0.3s;">Sobre mim</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="#habilidades" style="color: var(--text-muted); text-decoration: none; transition: color 0.3s;">Habilidades</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="#projetos" style="color: var(--text-muted); text-decoration: none; transition: color 0.3s;">Projetos</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="#blog" style="color: var(--text-muted); text-decoration: none; transition: color 0.3s;">Blog</a></li>
                    </ul>
                </div>

                <!-- Seção Projetos -->
                <div>
                    <h5 style="color: var(--accent); margin-bottom: 1rem; font-weight: 700;">Projetos</h5>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($footerProjects as $project): ?>
                            <?php if (!empty($project['primary_url'])): ?>
                                <li style="margin-bottom: 0.5rem;">
                                    <a href="<?= htmlspecialchars($project['primary_url']) ?>"<?= footer_link_target($project['primary_url']) ?> style="color: var(--text-muted); text-decoration: none; transition: color 0.3s;">
                                        <?= htmlspecialchars($project['title']) ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Seção Contato -->
                <div>
                    <h5 style="color: var(--accent); margin-bottom: 1rem; font-weight: 700;">Contato</h5>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <p style="color: var(--text-muted); margin: 0;">
                            <strong>Email:</strong><br>
                            <a href="mailto:iagochiapetta@gmail.com" style="color: var(--accent); text-decoration: none;">iagochiapetta@gmail.com</a>
                        </p>
                        <p style="color: var(--text-muted); margin: 0;">
                            <strong>WhatsApp:</strong><br>
                            <a href="https://wa.me/5521972940130?text=Olá" target="_blank" style="color: var(--accent); text-decoration: none;">+55 (21) 97294-0130</a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div style="border-top: 1px solid rgba(0, 217, 163, 0.1); padding-top: 2rem; margin-top: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <p style="color: var(--text-muted); margin: 0;">
                        © <?php echo date('Y'); ?> Iago Filgueiras Chiapetta. Todos os direitos reservados.
                    </p>
                    <div style="display: flex; gap: 1.5rem;">
                        <a href="https://github.com/chiapettaiago" target="_blank" style="color: var(--text-muted); text-decoration: none; transition: color 0.3s;" title="GitHub">GitHub</a>
                        <a href="https://www.linkedin.com/in/chiapettaiago" target="_blank" style="color: var(--text-muted); text-decoration: none; transition: color 0.3s;" title="LinkedIn">LinkedIn</a>
                        <a href="https://wa.me/5521972940130?text=Olá" target="_blank" style="color: var(--text-muted); text-decoration: none; transition: color 0.3s;" title="WhatsApp">WhatsApp</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <style>
        footer a:hover {
            color: var(--accent) !important;
        }
    </style>
</body>
</html>
