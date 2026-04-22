document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("export-cerfa-js");
  if (!btn) return;

  btn.addEventListener("click", async (e) => {
    e.preventDefault();
    e.stopPropagation();

    const mode   = btn.dataset.visaType;
    const postId = btn.dataset.postId;
    const url    = cerfaAjax.templateUrl + (mode === "court_sejour" ? "cs.html" : "ls.html");

    // 1) fetch + parse
    const html = await fetch(url).then(r => r.ok ? r.text() : Promise.reject(r.status));
    const parser = new DOMParser();
    const doc    = parser.parseFromString(html, "text/html");

    // 2) créer wrapper avec le <body> uniquement
    const wrapper = document.createElement("div");
    wrapper.innerHTML = doc.body.innerHTML;

    // 3) injecter les <style> du head pour garder votre mise en page
    doc.head.querySelectorAll("style, link[rel=stylesheet]").forEach(node => {
      wrapper.prepend(node.cloneNode(true));
    });

    // 4) positionner hors-champ + caché (html2canvas friendly)
    Object.assign(wrapper.style, {
      position:   "absolute",
      top:       "-9999px",
      left:      "-9999px",
      visibility: "hidden"
    });
    document.body.appendChild(wrapper);

    console.log("Template URL →", url);
    console.log("Contenu body chargé (longueur) →", wrapper.innerText.length);

    // 5) injecter vos champs
    const fields = [
      "level1_email", "level1_password", "visa_info_objet_base", /* … vos autres champs … */
      "visa_num_remplisseur"
    ];
    fields.forEach(name => {
      const src = document.querySelector(`[name="${name}"]`);
      const tgt = wrapper.querySelector(`#${name}`);
      if (tgt) tgt.textContent = src?.value || "";
      else console.warn(`Champ #${name} introuvable dans le template`);
    });

    // 6) génération PDF
    let pdfBlob;
    try {
      pdfBlob = await html2pdf()
        .set({
          margin: 10,
          filename: `cerfa-${mode}-${postId}.pdf`,
          image: { type: "jpeg", quality: 0.98 },
          html2canvas: { scale: 2, useCORS: true },
          jsPDF: { unit: "mm", format: "a4", orientation: "portrait" }
        })
        .from(wrapper)
        .outputPdf("blob");
      console.log("PDF généré, taille =", pdfBlob.size);
    } catch (err) {
      console.error("Erreur html2pdf:", err);
      document.body.removeChild(wrapper);
      return alert("Erreur lors de la génération du PDF. Regardez la console.");
    }

    document.body.removeChild(wrapper);

    // 7) upload
    const form = new FormData();
    form.append("action", "cerfa_upload_pdf");
    form.append("nonce",  cerfaAjax.nonce);
    form.append("post_id", postId);
    form.append("pdf",     pdfBlob, `cerfa-${mode}-${postId}.pdf`);

    const result = await fetch(cerfaAjax.ajaxurl, { method: "POST", body: form })
      .then(r => r.json());
    if (result.success) {
      alert("PDF enregistré avec succès !");
    } else {
      console.error(result);
      alert("Échec de l’enregistrement du PDF. Voir console.");
    }
  });
});