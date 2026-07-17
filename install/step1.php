<?php
global $reqCheck, $APPLICATION;
$reqCheck = is_array($reqCheck) ? $reqCheck : array();
$reqCheck['errors'] = isset($reqCheck['errors']) && is_array($reqCheck['errors']) ? $reqCheck['errors'] : array();
$reqCheck['warnings'] = isset($reqCheck['warnings']) && is_array($reqCheck['warnings']) ? $reqCheck['warnings'] : array();
$reqCheck['errors'] = array_map(function ($error) {
    return htmlspecialcharsbx((string)$error);
}, $reqCheck['errors']);
$reqCheck['warnings'] = array_map(function ($warning) {
    return htmlspecialcharsbx((string)$warning);
}, $reqCheck['warnings']);
$reqCheck['status'] = isset($reqCheck['status']) && is_array($reqCheck['status']) ? $reqCheck['status'] : array();
$reqStatus = array_values(array_filter($reqCheck['status'], function ($status) {
    return $status === 'ok' || $status === 'err';
}));
$reqStatusJson = json_encode($reqStatus, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$hasExistingClientKey = \COption::GetOptionString('drdroid.keyrights', 'clientPassphrase', '') !== ''
    || \COption::GetOptionString('drdroid.keyrights', 'clientPassphraseEncrypted', '') !== '';
?>
<div class="keyrightsBlock">
    <form action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>" method="post">
        <?=bitrix_sessid_post();?>
        <input type="hidden" name="lang" value="<?=htmlspecialcharsbx(LANGUAGE_ID)?>">
        <input type="hidden" name="step" value="1">
        <input type="hidden" name="id" value="drdroid.keyrights">
        <input type="hidden" name="install" value="Y">

        <?php  if (!empty($GLOBALS['errors']) && !empty($GLOBALS['errors']['keyphrase'])) : ?>
            <h2 style="color: #f00;"><?= GetMessage('KEYRIGHTS_INSTALL_PASS_EMPTY'); ?></h2>
        <?php  endif; ?>
        <?php  if (!empty($GLOBALS['errors']) && !empty($GLOBALS['errors']['licence'])) : ?>
            <h2 style="color: #f00;"><?= GetMessage('KEYRIGHTS_INSTALL_LICENSE_REQUIRED'); ?></h2>
        <?php  endif; ?>

        <table style="max-width: 1055px;border:0;border-collapse: collapse;">
            <tr>
                <td style="vertical-align: top;padding-bottom:20px;">
                    <h2 style="background-color: transparent; margin:0;padding:0;font:bold 30px/50px Arial,sans-serif;color:#333;"><?=GetMessage("KEYRIGHTS_INSTALL_PROCESS_TITLE")?></h2>
                    <h3 style="background-color: transparent; margin:0;padding:0;font:bold 14px/20px Arial,sans-serif;color:#333;"><?=GetMessage("KEYRIGHTS_INSTALL_PROCESS_DESCRIPTION")?></h3>
                    <h4 style="background-color: transparent; margin:0;padding:0;font:normal 12px/16px Arial,sans-serif;color:#333;"><?=GetMessage("KEYRIGHTS_INSTALL_REQUIREMENTS_HEADER")?></h4>
                    <ul style="padding:0;margin:20px 0;list-style:none;font:normal 12px/22px Arial,sans-serif;color:#333;" class="requirements">
                        <?= GetMessage("KEYRIGHTS_INSTALL_REQUIREMENTS"); ?>
                    </ul>
                </td>
            </tr>
            <tr>
                <?php  if (count($reqCheck['errors'])) { ?>
                    <td style="padding-top:25px;font: bold 12px/18px Arial,sans-serif;color:#333;">
                        <h3 style="background-color: transparent;padding:0;font:bold 14px/20px Arial,sans-serif;color:#333;"><?=GetMessage("KEYRIGHTS_INSTALL_REQUIREMENTS_ERROR")?></h3>
                        <ul style="padding:0;margin:20px 0;list-style:none;font:normal 12px/22px Arial,sans-serif;color:#333;">
                            <li>
                                <img style='vertical-align:middle' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAAPCAYAAAA71pVKAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAZdJREFUeNqcU7tqAkEUPfsAH2AhbnCtFowgVoLiNiKIpo6QQvIT+QPLJR8QIb8RYm1AVBSyjW1E8VFqJdqIr9wZZpdFiUgOHAaGc+69O/esdDqd4KBWq93R8Uh8IOaIBnFGtIlNYsOyrKWjlxwzGct0vEiSVPEWPMMnsU4FvlyzML4STVxBIBBAPB7/iUajb6VS6V3Z7/dsVItYdseRpAsju0ulUigWi1ooFMq22+0PWXxjhQlkWUYikUAkEkE4HHaNPp8P6XQahUIBuq6DptUXi8WTLB4HjtkwDFSrVeTzeV5AVVUkk0lujMVimE6noK7szKriVTkOhwO22y3om6BpGusA6gDTNPmdY5zP50yeU8U6OJh4MBggGAxyQyaTwfF45A81mUzQ7XYxHA4duSGLPbrYbDbo9/uwbRuKonAj69jpdDAej73SmSoCcO+9Xa/X6PV68Pv92O123DQajfgUHtiqSM7z+WpYgVarhdVq9dfam2zshkjOBa4Ymb4hi6zWid+4Dd8iosuLbDuBuTnb//2rfgUYAGhesiHiJE6DAAAAAElFTkSuQmCC' alt=''
                                >&nbsp;&nbsp;&nbsp;<?=(implode("</li><li><img style='vertical-align:middle' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAAPCAYAAAA71pVKAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAZdJREFUeNqcU7tqAkEUPfsAH2AhbnCtFowgVoLiNiKIpo6QQvIT+QPLJR8QIb8RYm1AVBSyjW1E8VFqJdqIr9wZZpdFiUgOHAaGc+69O/esdDqd4KBWq93R8Uh8IOaIBnFGtIlNYsOyrKWjlxwzGct0vEiSVPEWPMMnsU4FvlyzML4STVxBIBBAPB7/iUajb6VS6V3Z7/dsVItYdseRpAsju0ulUigWi1ooFMq22+0PWXxjhQlkWUYikUAkEkE4HHaNPp8P6XQahUIBuq6DptUXi8WTLB4HjtkwDFSrVeTzeV5AVVUkk0lujMVimE6noK7szKriVTkOhwO22y3om6BpGusA6gDTNPmdY5zP50yeU8U6OJh4MBggGAxyQyaTwfF45A81mUzQ7XYxHA4duSGLPbrYbDbo9/uwbRuKonAj69jpdDAej73SmSoCcO+9Xa/X6PV68Pv92O123DQajfgUHtiqSM7z+WpYgVarhdVq9dfam2zshkjOBa4Ymb4hi6zWid+4Dd8iosuLbDuBuTnb//2rfgUYAGhesiHiJE6DAAAAAElFTkSuQmCC' alt=''>&nbsp;&nbsp;&nbsp;", $reqCheck['errors']));?>
                            </li>
                        </ul>
                        <h3 style="background-color: transparent;padding:0;font:bold 14px/20px Arial,sans-serif;color:#333;"><?=GetMessage("KEYRIGHTS_INSTALL_REQUIREMENTS_REPAIR")?></h3>
                    </td>
                <?php  } else { ?>
                    <td style="padding-top:25px;text-align:center;font: bold 12px/18px Arial,sans-serif;color:#333;">
                        <label>
                            <?= GetMessage('KEYRIGHTS_INSTALL_PASS_LABEL'); ?>
                            <input
                                type="password"
                                id="keyphrase"
                                name="keyphrase"
                                minlength="16"
                                size="40"
                                autocomplete="new-password"
                            />
                        </label>
                        <?php if (!$hasExistingClientKey) { ?>
                            <button type="button" id="generateKeyphrase" class="adm-btn">
                                <?= GetMessage('KEYRIGHTS_INSTALL_PASS_GENERATE'); ?>
                            </button>
                            <div id="generatedKeyphraseNotice" style="display:none;margin-top:8px;color:#555;">
                                <?= GetMessage('KEYRIGHTS_INSTALL_PASS_GENERATED_NOTICE'); ?>
                            </div>
                        <?php } else { ?>
                            <div style="margin-top:8px;color:#555;">
                                <?= GetMessage('KEYRIGHTS_INSTALL_PASS_ALREADY_EXISTS_REWRITE'); ?>
                            </div>
                        <?php } ?>
                        <br><br>
                        <label>
                            <input type="checkbox" id="licence_agree" name="licence_agree" value="Y" style="vertical-align: bottom;" />&nbsp;&nbsp;
                            <?=GetMessage("KEYRIGHTS_LICENSE_STEP1")?>
                        </label>
                        <br><br>
                        <input type="submit" name="install" id="installButton" disabled="disabled" value="<?=GetMessage("KEYRIGHTS_INSTALL_BUTTON_INSTALL")?>"/>
                    </td>
                <?php  } ?>
            </tr>
            <?php if (count($reqCheck['warnings'])) { ?>
                <tr>
                    <td style="padding-top:15px;font:normal 12px/18px Arial,sans-serif;color:#8a6000;">
                        <strong><?= GetMessage('KEYRIGHTS_INSTALL_REQUIREMENTS_WARNING') ?></strong>
                        <ul style="margin:8px 0 0;padding-left:20px;">
                            <?php foreach ($reqCheck['warnings'] as $warning) { ?>
                                <li><?= $warning ?></li>
                            <?php } ?>
                        </ul>
                    </td>
                </tr>
            <?php } ?>
        </table>


        <script type="text/javascript">
            var imgErr = "<img style='vertical-align:middle' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAAPCAYAAAA71pVKAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAZdJREFUeNqcU7tqAkEUPfsAH2AhbnCtFowgVoLiNiKIpo6QQvIT+QPLJR8QIb8RYm1AVBSyjW1E8VFqJdqIr9wZZpdFiUgOHAaGc+69O/esdDqd4KBWq93R8Uh8IOaIBnFGtIlNYsOyrKWjlxwzGct0vEiSVPEWPMMnsU4FvlyzML4STVxBIBBAPB7/iUajb6VS6V3Z7/dsVItYdseRpAsju0ulUigWi1ooFMq22+0PWXxjhQlkWUYikUAkEkE4HHaNPp8P6XQahUIBuq6DptUXi8WTLB4HjtkwDFSrVeTzeV5AVVUkk0lujMVimE6noK7szKriVTkOhwO22y3om6BpGusA6gDTNPmdY5zP50yeU8U6OJh4MBggGAxyQyaTwfF45A81mUzQ7XYxHA4duSGLPbrYbDbo9/uwbRuKonAj69jpdDAej73SmSoCcO+9Xa/X6PV68Pv92O123DQajfgUHtiqSM7z+WpYgVarhdVq9dfam2zshkjOBa4Ymb4hi6zWid+4Dd8iosuLbDuBuTnb//2rfgUYAGhesiHiJE6DAAAAAElFTkSuQmCC' alt=''>";
            var imgOk  = "<img style='vertical-align:middle' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAAPCAMAAAAMCGV4AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAN5QTFRF////sdsAstsAstwA0eplsdsAstsAstwAsdsAstsAstwA2u5/xeQnsdsAstsAstwAsdsAstsAstwAsdsAstsAstwAy+dKsdsAstsAstwA7ffEsdsAstsAstwAsdsAstsAstwAtNwAtd0Att0Att4AuN4Aut8AuuAAvOAAveEAvuEAx+U0x+U4yOY8yOc8yeY9yec+zuhSzuhW0uti1exx2e6A2++I3fCQ3vCS3vCV4PGb7PbD7PfC7ffC7ffE8PjQ8fnP8fnU8/rV+v3w+/3u+/3y+/30/f74/f76////U7vhWQAAAB50Uk5TABUVFSJDQ0NjY2NqdKenp7CwsMnJydjc3Nzn+/v749xnEwAAAKlJREFUCB0FwdF1gzAQRcH7VkIIMDh2Aem/OH9wQCQQAZsZAcopx2urfzcI0jCBI81bJZDGUS4kBqMGmx5CGLpf3+U35Lf52MRKeE/d8InJCf1waH9+tdtsscPdc/L+0ZZ578LLUKXJOa/rclroovy+1MdlXd1q3FuT7x6vY3Fni1UO2j+HC7yG01rATrmhUgK3RSF3wVbOwFXVupBYy4GA0KSuqT/XfsM/UuBSYeWg4tEAAAAASUVORK5CYII=' alt=''>";
            function initReqState(state) {
                var elements = document.querySelectorAll('.requirements li');
                for (var i = 0; i < state.length && i < elements.length; i++) {
                    if (state[i] == "ok") {
                        elements[i].insertAdjacentHTML('afterbegin', imgOk + '&nbsp;&nbsp;&nbsp;');
                    } else {
                        elements[i].insertAdjacentHTML('afterbegin', imgErr + '&nbsp;&nbsp;&nbsp;');
                    }
                }
            }

            function generateKeyphrase() {
                if (!window.crypto || typeof window.crypto.getRandomValues !== 'function' || typeof window.btoa !== 'function') {
                    return '';
                }
                var bytes = new Uint8Array(24);
                window.crypto.getRandomValues(bytes);
                var binary = '';
                for (var i = 0; i < bytes.length; i++) {
                    binary += String.fromCharCode(bytes[i]);
                }
                return window.btoa(binary)
                    .replace(/\+/g, '-')
                    .replace(/\//g, '_')
                    .replace(/=+$/, '');
            }

            function fillGeneratedKeyphrase() {
                var input = document.getElementById('keyphrase');
                var generated = generateKeyphrase();
                if (!input || generated === '') {
                    return false;
                }
                input.value = generated;
                input.type = 'text';
                var notice = document.getElementById('generatedKeyphraseNotice');
                if (notice) {
                    notice.style.display = 'block';
                }
                input.focus();
                input.select();
                return true;
            }

            function validateInputs() {
                var licence = document.getElementById('licence_agree');
                var button = document.getElementById('installButton');
                if (button) {
                    button.disabled = !licence || !licence.checked;
                }
            }

            function initInstallerForm() {
                initReqState(<?= $reqStatusJson ?: '[]' ?>);

                var licence = document.getElementById('licence_agree');
                if (licence) {
                    licence.addEventListener('change', validateInputs);
                }

                var generator = document.getElementById('generateKeyphrase');
                if (generator) {
                    generator.addEventListener('click', fillGeneratedKeyphrase);
                    if (!fillGeneratedKeyphrase()) {
                        generator.disabled = true;
                    }
                } else {
                    var input = document.getElementById('keyphrase');
                    if (input) {
                        input.focus();
                    }
                }

                validateInputs();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initInstallerForm);
            } else {
                initInstallerForm();
            }
        </script>


    </form>
</div>
