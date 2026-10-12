// Browser checklist for cb-qr-code, run by tests/browser.sh through
// `playwright-cli run-code`. A single function expression: (page, CFG) => results.
//
// Starts from a fresh install (no saved options) and exercises everything a
// person would click: tabs, live preview, the media picker, both saves, and
// click-to-copy on the frontend. Returns one entry per check plus every console
// error seen on the plugin's pages.
async (page, CFG) => {
  const results = [];
  const consoleErrors = [];
  const check = (name, pass, detail) => results.push({ name, pass: !!pass, detail: detail === undefined ? '' : String(detail) });

  page.on('console', (msg) => { if (msg.type() === 'error') consoleErrors.push(page.url() + ' :: ' + msg.text()); });
  page.on('pageerror', (err) => consoleErrors.push(page.url() + ' :: pageerror :: ' + err.message));

  const adminPage = CFG.site + '/wp-admin/admin.php?page=cbqrcode_admin_dashboard';
  const shot = (name) => page.screenshot({ path: CFG.screens + '/' + name, fullPage: true });

  // Wait for the next preview AJAX round trip triggered by `action`.
  const withPreview = async (action) => {
    const done = page.waitForResponse((r) => r.url().includes('admin-ajax.php') && (r.request().postData() || '').includes('action=cbqrcode_preview'));
    await action();
    await done;
    await page.waitForTimeout(150);
  };
  const withSave = async (action) => {
    const done = page.waitForResponse((r) => r.url().includes('admin-ajax.php') && (r.request().postData() || '').includes('action=cbqrcode_save_settings'));
    await action();
    await done;
    await page.waitForTimeout(150);
  };
  const visibleTab = async () => page.evaluate(() => {
    const shown = [...document.querySelectorAll('.cbqrcode-tab-content')].filter((el) => el.style.display !== 'none');
    return shown.map((el) => el.id).join(',');
  });
  const previewSrc = async () => page.evaluate(() => { const img = document.querySelector('#cbqrcode-preview img'); return img ? img.getAttribute('src') : ''; });

  // 0. Log in.
  await page.goto(CFG.site + '/wp-login.php');
  await page.fill('#user_login', CFG.user);
  await page.fill('#user_pass', CFG.pass);
  await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);
  check('login', page.url().includes('/wp-admin'), page.url());

  // 1. Admin page renders, initial preview arrives.
  await page.goto(adminPage);
  await page.evaluate(() => localStorage.removeItem('cbqrcode_active_tab'));
  await withPreview(() => page.reload());
  check('1 dashboard renders', await page.locator('.cbqrcode-dashboard').count() === 1);
  check('1 initial preview image', (await previewSrc()).startsWith('data:image/png;base64,'));
  await shot('01-admin.png');

  // 2. Tabs switch, and the last one is remembered across a reload.
  for (const tab of ['settings', 'about', 'support', 'appearance']) {
    await page.click('.cbqrcode-tabs-nav button[data-tab="' + tab + '"]');
    const shown = await visibleTab();
    check('2 tab ' + tab + ' shows alone', shown === 'cbqrcode-tab-' + tab, shown);
  }
  await page.click('.cbqrcode-tabs-nav button[data-tab="settings"]');
  await withPreview(() => page.reload());
  check('2 tab remembered after reload', (await visibleTab()) === 'cbqrcode-tab-settings', await visibleTab());
  await page.click('.cbqrcode-tabs-nav button[data-tab="appearance"]');

  // 3. Live preview follows the label and colour.
  await withPreview(() => page.fill('#qr-code-label', 'Browser QR'));
  check('3 preview label follows input', (await page.locator('#cbqrcode-preview .cbqrcode-label').textContent()).trim() === 'Browser QR');
  // Blur the label and let its `change` preview land first. Preview requests
  // aren't sequenced (issues.md #13), so an in-flight old-colour response could
  // otherwise overwrite the new one and make this check flaky.
  await withPreview(() => page.locator('#qr-code-label').evaluate((el) => el.blur()));
  const before = await previewSrc();
  await withPreview(() => page.fill('#qr-code-dark', 'aa0000'));
  check('3 preview image follows colour', (await previewSrc()) !== before);
  await shot('02-preview.png');

  // 4. Media picker: select, remove, select again.
  const pickLogo = async () => {
    await page.click('.cbqrcode-select-media');
    await page.locator('.media-modal').last().waitFor({ state: 'visible' });
    // The modal reopens on the user's last tab; switch to the library if it's on Upload.
    const browse = page.locator('.media-modal #menu-item-browse').last();
    if (await browse.count()) await browse.click();
    const item = page.locator('.media-modal li.attachment[data-id="' + CFG.logoId + '"]').last();
    await item.waitFor({ state: 'visible', timeout: 15000 });
    await item.click();
    await withPreview(() => page.locator('.media-modal .media-button-select').last().click());
  };
  const noLogoSrc = await previewSrc();
  await pickLogo();
  check('4 logo id set', (await page.inputValue('#qr-code-logo-id')) === String(CFG.logoId), await page.inputValue('#qr-code-logo-id'));
  check('4 logo name shown', (await page.locator('.cbqrcode-selected-image-name').textContent()).includes('cbqr-test-logo'), await page.locator('.cbqrcode-selected-image-name').textContent());
  check('4 remove button visible', await page.locator('.cbqrcode-remove-media').isVisible());
  check('4 preview changes with logo', (await previewSrc()) !== noLogoSrc);
  await withPreview(() => page.click('.cbqrcode-remove-media'));
  check('4 remove clears id', (await page.inputValue('#qr-code-logo-id')) === '');
  check('4 remove resets name', (await page.locator('.cbqrcode-selected-image-name').textContent()).trim() === 'No image selected');
  await pickLogo();

  // 5. Save Appearance, values survive a reload.
  await withSave(() => page.click('.header-submit-btn'));
  check('5 appearance save notice', await page.locator('#cbqrcode-appearance-form .notice-success').count() === 1, await page.locator('#cbqrcode-appearance-form .notice').allTextContents());
  await withPreview(() => page.reload());
  check('5 label persisted', (await page.inputValue('#qr-code-label')) === 'Browser QR');
  check('5 dark persisted', (await page.inputValue('#qr-code-dark')) === 'aa0000');
  check('5 logo persisted', (await page.inputValue('#qr-code-logo-id')) === String(CFG.logoId));

  // 6. Settings tab: custom URL validation, save, persist, switch back.
  await page.click('.cbqrcode-tabs-nav button[data-tab="settings"]');
  await page.check('input[name="cbqrcode-url-mode"][value="custom"]');
  check('6 custom url field shows', await page.locator('#cbqrcode-custom-url').isVisible());
  await page.fill('#cbqrcode-custom-url', '');
  await withSave(() => page.click('.header-submit-btn'));
  const err = await page.locator('#cbqrcode-tab-settings .notice-error').allTextContents();
  check('6 empty custom url rejected', err.join(' ').includes('URL is required'), err.join(' | '));
  await page.fill('#cbqrcode-custom-url', 'https://example.com/?x=1&y=2');
  await withSave(() => page.click('.header-submit-btn'));
  check('6 custom url saved', await page.locator('#cbqrcode-tab-settings .notice-success').count() === 1);
  await withPreview(() => page.reload());
  check('6 settings tab remembered', (await visibleTab()) === 'cbqrcode-tab-settings');
  check('6 custom mode persisted', await page.isChecked('input[name="cbqrcode-url-mode"][value="custom"]'));
  check('6 custom url persisted', (await page.inputValue('#cbqrcode-custom-url')) === 'https://example.com/?x=1&y=2', await page.inputValue('#cbqrcode-custom-url'));
  await page.check('input[name="cbqrcode-url-mode"][value="permalink"]');
  check('6 custom url field hides', !(await page.locator('#cbqrcode-custom-url').isVisible()));
  await withSave(() => page.click('.header-submit-btn'));
  check('6 permalink saved', await page.locator('#cbqrcode-tab-settings .notice-success').count() === 1);

  // 7. Frontend: QR shows with the saved label, click copies.
  await page.context().grantPermissions(['clipboard-read', 'clipboard-write'], { origin: CFG.site });
  await page.goto(CFG.site + '/?p=' + CFG.postId);
  const qr = page.locator('.cbqrcode-qr');
  check('7 one QR on the post', await qr.count() === 1, await qr.count());
  check('7 QR label is saved label', (await page.locator('.cbqrcode-qr .cbqrcode-label').textContent()).trim() === 'Browser QR');
  check('7 QR encodes permalink', (await qr.getAttribute('data-url')) === CFG.site + '/?p=' + CFG.postId, await qr.getAttribute('data-url'));
  await qr.scrollIntoViewIfNeeded();
  await qr.click();
  let copied = false;
  for (let i = 0; i < 10 && !copied; i++) {
    copied = (await qr.getAttribute('class')).includes('copied');
    if (!copied) await page.waitForTimeout(25);
  }
  check('7 click adds copied class', copied);
  const clip = await page.evaluate(() => navigator.clipboard.readText()).catch((e) => 'ERR ' + e.message);
  check('7 clipboard holds the URL', clip === CFG.site + '/?p=' + CFG.postId, clip);
  await shot('03-frontend.png');

  check('console has no errors', consoleErrors.length === 0, consoleErrors.join(' || '));
  return { passed: results.filter((r) => r.pass).length, failed: results.filter((r) => !r.pass).length, results };
}
