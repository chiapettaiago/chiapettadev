<?php
require_once __DIR__ . '/../admin/modules/SlideDeck.php';

$slideDeckSlug = $slideDeckSlug ?? '';
$deck = SlideDeck::getBySlug($slideDeckSlug);

if (!$deck || $deck['status'] !== 'published') {
    http_response_code(404);
    include __DIR__ . '/../404.html';
    exit;
}

$slides = $deck['slides'] ?? [];
$pageTitle = $deck['title'] . ' | ChiapettaDev';
?>
<?php include __DIR__ . '/header.php'; ?>

<main class="slide-page">
    <section class="slide-shell" aria-label="<?= htmlspecialchars($deck['title']) ?>">
        <div class="slide-stage" id="slideStage">
            <?php foreach ($slides as $index => $slide): ?>
                <article class="deck-slide <?= $index === 0 ? 'active' : '' ?>" data-slide="<?= $index ?>" aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>">
                    <div class="deck-slide-content">
                        <div class="deck-slide-kicker">Slide <?= $index + 1 ?> de <?= count($slides) ?></div>
                        <?php if (!empty($slide['title'])): ?>
                            <h1><?= htmlspecialchars($slide['title']) ?></h1>
                        <?php endif; ?>
                        <?php if (!empty($slide['content'])): ?>
                            <div class="deck-slide-body"><?= $slide['content'] ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($slide['image'])): ?>
                        <div class="deck-slide-media">
                            <img src="<?= htmlspecialchars($slide['image']) ?>" alt="<?= htmlspecialchars($slide['title'] ?: $deck['title']) ?>">
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="slide-controls">
            <button type="button" class="slide-button" id="prevSlide">
                <i class="fas fa-chevron-left"></i>
                Voltar
            </button>
            <div class="slide-progress" aria-live="polite">
                <span id="currentSlide">1</span>/<span><?= count($slides) ?></span>
            </div>
            <button type="button" class="slide-button" id="nextSlide">
                Avançar
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </section>
</main>

<style>
    .slide-page {
        min-height: calc(100vh - 84px);
        display: flex;
        align-items: stretch;
        background: radial-gradient(circle at 20% 20%, rgba(0, 217, 163, 0.12), transparent 32rem), #111;
    }

    .slide-shell {
        width: 100%;
        min-height: calc(100vh - 84px);
        display: grid;
        grid-template-rows: 1fr auto;
        border-top: none;
        padding: 0;
    }

    .slide-stage {
        position: relative;
        min-height: 70vh;
        overflow: hidden;
    }

    .deck-slide {
        position: absolute;
        inset: 0;
        display: grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(280px, 0.9fr);
        gap: 3rem;
        align-items: center;
        width: 100%;
        padding: clamp(2rem, 6vw, 5rem);
        opacity: 0;
        transform: translateX(3rem);
        pointer-events: none;
        transition: opacity 0.28s ease, transform 0.28s ease;
    }

    .deck-slide.active {
        opacity: 1;
        transform: translateX(0);
        pointer-events: auto;
    }

    .deck-slide:not(:has(.deck-slide-media)) {
        grid-template-columns: minmax(0, 900px);
        justify-content: center;
        text-align: center;
    }

    .deck-slide-kicker {
        color: var(--accent);
        font-size: 0.85rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }

    .deck-slide h1 {
        font-size: clamp(2.3rem, 6vw, 5.4rem);
        line-height: 1.02;
        margin-bottom: 1.25rem;
    }

    .deck-slide-body {
        color: var(--text-muted);
        font-size: clamp(1.05rem, 2vw, 1.35rem);
        line-height: 1.75;
    }

    .deck-slide-body ul,
    .deck-slide-body ol {
        text-align: left;
        display: inline-block;
    }

    .deck-slide-media img {
        width: 100%;
        max-height: 62vh;
        object-fit: contain;
        border-radius: 12px;
        border: 1px solid rgba(0, 217, 163, 0.16);
        box-shadow: 0 26px 80px rgba(0, 0, 0, 0.35);
    }

    .slide-controls {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        padding: 1.25rem;
        background: rgba(0, 0, 0, 0.28);
        border-top: 1px solid rgba(0, 217, 163, 0.12);
    }

    .slide-button {
        min-width: 132px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        border: 1px solid rgba(0, 217, 163, 0.28);
        border-radius: 8px;
        background: rgba(0, 217, 163, 0.1);
        color: var(--text);
        padding: 0.75rem 1.1rem;
        font-weight: 800;
    }

    .slide-button:disabled {
        opacity: 0.42;
        cursor: not-allowed;
    }

    .slide-progress {
        color: var(--text-muted);
        font-weight: 800;
        min-width: 74px;
        text-align: center;
    }

    @media (max-width: 860px) {
        .deck-slide {
            grid-template-columns: 1fr;
            gap: 1.5rem;
            text-align: center;
        }

        .slide-controls {
            justify-content: space-between;
        }

        .slide-button {
            min-width: auto;
        }
    }
</style>

<script>
    const slides = Array.from(document.querySelectorAll('.deck-slide'));
    const currentSlide = document.getElementById('currentSlide');
    const prevSlide = document.getElementById('prevSlide');
    const nextSlide = document.getElementById('nextSlide');
    let activeSlide = 0;

    function showSlide(index) {
        activeSlide = Math.max(0, Math.min(index, slides.length - 1));

        slides.forEach((slide, slideIndex) => {
            const isActive = slideIndex === activeSlide;
            slide.classList.toggle('active', isActive);
            slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });

        currentSlide.textContent = String(activeSlide + 1);
        prevSlide.disabled = activeSlide === 0;
        nextSlide.disabled = activeSlide === slides.length - 1;
    }

    prevSlide.addEventListener('click', () => showSlide(activeSlide - 1));
    nextSlide.addEventListener('click', () => showSlide(activeSlide + 1));

    document.addEventListener('keydown', event => {
        if (event.key === 'ArrowLeft') showSlide(activeSlide - 1);
        if (event.key === 'ArrowRight' || event.key === ' ') showSlide(activeSlide + 1);
    });

    showSlide(0);
</script>

<?php include __DIR__ . '/footer.php'; ?>
