<?php $intranet_versies = get_intranet_versies($pdo); ?>
    <p class="footer-note">
        MK Intranet &middot; gekoppeld aan het meldkamersysteem
        <button type="button" class="versie-knop" onclick="document.getElementById('wijzigingen-dialog').showModal()"><?= e(huidige_intranet_versie($pdo)) ?></button>
    </p>

    <dialog id="wijzigingen-dialog" class="wijzigingen-dialog">
        <div class="wijzigingen-kop">
            <h2>Wat is er nieuw</h2>
            <button type="button" class="wijzigingen-sluiten" onclick="document.getElementById('wijzigingen-dialog').close()" aria-label="Sluiten">&times;</button>
        </div>
        <div class="wijzigingen-inhoud">
            <?php if (!$intranet_versies): ?>
                <p class="section-note">Nog geen wijzigingenlog beschikbaar.</p>
            <?php endif; ?>
            <?php foreach ($intranet_versies as $index => $release): ?>
                <?php $is_nieuwste = $index === 0; ?>
                <div class="wijzigingen-release">
                    <div class="wijzigingen-release-head">
                        <p class="wijzigingen-release-kop"><?= e($release['versienummer']) ?> <span>&middot; <?= e($release['datum']) ?></span></p>
                        <?php if (!$is_nieuwste): ?>
                            <label for="versie-toggle-<?= $release['id'] ?>" class="log-toggle-wrap" title="In-/uitklappen">
                                <span class="log-toggle-switch"></span>
                            </label>
                        <?php endif; ?>
                    </div>
                    <?php if ($is_nieuwste): ?>
                        <?= render_wijzigingen_html($release['wijzigingen']) ?>
                    <?php else: ?>
                        <input type="checkbox" id="versie-toggle-<?= $release['id'] ?>" class="log-toggle-checkbox">
                        <div class="row-log"><?= render_wijzigingen_html($release['wijzigingen']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </dialog>
</div>
</body>
</html>
