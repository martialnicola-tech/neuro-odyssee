/**
 * initNewsletterForm(formId)
 * Réutilisable sur toutes les pages, gère le submit d'un formulaire newsletter.
 * Le formulaire doit contenir des champs name="email" et (optionnel) name="prenom".
 * Le message de retour est affiché dans l'élément id="{formId}Msg".
 */
function initNewsletterForm(formId) {
  var form = document.getElementById(formId);
  if (!form) return;

  var msgEl = document.getElementById(formId + 'Msg');
  var btn   = form.querySelector('[type="submit"]');

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    var emailInput  = form.querySelector('[name="email"]');
    var prenomInput = form.querySelector('[name="prenom"]');

    if (!emailInput) return;

    var email  = emailInput.value.trim();
    var prenom = prenomInput ? prenomInput.value.trim() : '';

    if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
    if (msgEl) { msgEl.style.color = 'rgba(255,255,255,0.5)'; msgEl.textContent = '⏳ Envoi en cours…'; }

    try {
      var fd = new FormData();
      fd.append('email',  email);
      fd.append('prenom', prenom);

      var res  = await fetch('payment/newsletter.php', { method: 'POST', body: fd });
      var data = await res.json();

      if (data.success) {
        if (msgEl) { msgEl.style.color = '#6de0c8'; msgEl.textContent = '✓ Vérifiez votre boîte email, l\'ebook est en route !'; }
        emailInput.value = '';
        if (prenomInput) prenomInput.value = '';
        if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
      } else {
        if (msgEl) { msgEl.style.color = '#ffaaaa'; msgEl.textContent = 'Une erreur est survenue. Réessayez.'; }
        if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
      }
    } catch (err) {
      if (msgEl) { msgEl.style.color = '#ffaaaa'; msgEl.textContent = 'Une erreur est survenue. Réessayez.'; }
      if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
    }
  });
}
