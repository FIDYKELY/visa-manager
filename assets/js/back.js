document.addEventListener("DOMContentLoaded", () => { 
    const btn = document.getElementById("export-cerfa-js"); 
    if (!btn) return;
    
    btn.addEventListener("click", async (e) => { 
        e.preventDefault(); 
        e.stopPropagation();
        // 1) Construire l’URL vers test.html
        // const url = cerfaAjax.templateUrl + "ls.html";
        const mode   = btn.dataset.visaType;
        const postId = btn.dataset.postId;
        const url    = cerfaAjax.templateUrl + (mode === "court_sejour" ? "cs.html" : "ls.html");
        console.log("Fetch template →", url);
        
        // 2) Récupérer et parser le HTML
        let html;
        try {
          const res = await fetch(url);
          if (!res.ok) throw new Error(res.statusText);
          html = await res.text();
        } catch (err) {
          console.error("Erreur fetch test.html:", err);
          return alert("Impossible de charger test.html");
        }
        
        const parser = new DOMParser();
        const doc    = parser.parseFromString(html, "text/html");
        
        // 3) Préparer le wrapper avec le <body> du template
        const wrapper = document.createElement("div");
        wrapper.innerHTML = doc.body.innerHTML;
        
        // 4) Injecter les <style> du head pour conserver le design
        doc.head.querySelectorAll("style, link[rel=stylesheet]").forEach(node => {
          wrapper.prepend(node.cloneNode(true));
        });
        
        // 5) Styles pour debug (sans invisibilité pour voir le rendu)
        Object.assign(wrapper.style, {
          width:      "800px",
          // height:     "1120px",
          background: "#fff"
          // Une fois validé, tu peux dé-commenter :
          //position:   "absolute",
          //top:        "-9999px",
          //left:       "-9999px",
          //visibility: "hidden"
        });
        document.body.appendChild(wrapper);
        
        // Injecter les images dans le template
        const imgUe    = wrapper.querySelector('img[alt="ue"]');
        const imgCerfa = wrapper.querySelector('img[alt="cerfa"]');
        const imgLef = wrapper.querySelector('img[alt="LEF"]');
        
        if (imgUe) {
          imgUe.src = cerfaAjax.photoUeUrl;
          console.log(`Image UE injectée → ${cerfaAjax.photoUeUrl}`);
        }
        
        if (imgCerfa) {
          imgCerfa.src = cerfaAjax.photoCerfaUrl;
          console.log(`Image Cerfa injectée → ${cerfaAjax.photoCerfaUrl}`);
        }
        
        if (imgLef) {
          imgLef.src = cerfaAjax.photoLefaUrl;
          console.log(`Image Lef injectée → ${cerfaAjax.photoLefaUrl}`);
        }
        
        // juste après avoir append wrapper…
        const fields = document.querySelectorAll('input[name], select[name], textarea[name]');
        
        fields.forEach(srcEl => {
          const { tagName: tag, type, name, value: val, checked } = srcEl;
        
          // 1) Gestion de TOUTES les checkbox en tableau (name="xxx[]")
          if (tag === "INPUT" && type === "checkbox" && name.endsWith("[]")) {
            const baseName = name.slice(0, -2);  // ex: "visa_demandeur_financement_moyen" ou "visa_lien_parente"
            
            // 2) On cherche d’abord par id=baseName + name=val
            let boxes = wrapper.querySelectorAll(
              `input[id="${CSS.escape(baseName)}"][name="${CSS.escape(val)}"]`
            );
        
            // 3) Fallback : si rien n’a été trouvé (cas lien_parenté)
            if (boxes.length === 0) {
              boxes = wrapper.querySelectorAll(
                `input[type="checkbox"][name="${CSS.escape(val)}"]`
              );
            }
        
            // 4) On coche/décoche toutes les cases trouvées
            boxes.forEach(box => box.checked = checked);
        
            console.log(`â [${baseName}] ${val} → checked=${checked}`);
            return;
          }
          
          if (name.endsWith("[]")) {
            const baseName = name.slice(0, -2);
            const srcEls   = document.querySelectorAll(`[name="${baseName}[]"]`);
            const container = wrapper.querySelector(`#${baseName}`);
        
            if (!container) {
              console.warn(`Pas de container trouvé pour #${baseName}`);
              return; // passe au champ suivant
            }
        
            container.innerHTML = "";
        
            srcEls.forEach(el => {
              const div = document.createElement("div");
              div.textContent = el.value;
              container.appendChild(div);
            });
        
            console.log(`[${baseName}] → ${srcEls.length} lignes injectées`);
            return; // on sort pour ce srcEl
          }

        
          // 1) on cherche d’abord un élément par ID, puis par name
          let targetEl = wrapper.querySelector(`#${CSS.escape(name)}`);
          if (!targetEl) {
            targetEl = wrapper.querySelector(`[name="${CSS.escape(name)}"]`);
          }
          if (!targetEl) {
            console.warn(`Aucun élément trouvable pour name="${name}" dans le template`);
            return;
          }
        
          // 2) on traite selon le type de champ
          switch (srcEl.tagName) {
            case "INPUT":
                
              if (srcEl.type === "radio") {
                const id = name;           // le champ du formulaire a name="sexe" ou "civilité"
                const value = srcEl.value; // "homme", "femme", etc.
            
                // 1) récupérer tous les éléments du template avec id identique
                const candidates = wrapper.querySelectorAll(`#${CSS.escape(id)}`);
            
                // 2) parmi eux, chercher celui dont le name === value
                const match = Array.from(candidates).find(el => el.name === value);
            
                if (match) {
                  match.checked = srcEl.checked;
                  console.log(`[${id}] name="${value}" → checked=${srcEl.checked}`);
                } else {
                  console.warn(`Aucun match dans wrapper pour id="${id}" + name="${value}"`);
                }
            
                return; // on sort pour ne pas toucher textContent/value
              }

              // texte, email, number…
              targetEl.value       = srcEl.value;
              targetEl.textContent = srcEl.value;
              console.log(`${name} → "${srcEl.value}"`);
              break;
        
            case "SELECT":
              const opt = srcEl.options[srcEl.selectedIndex];
              if (opt && opt.value) {
                targetEl.value       = opt.value;
                targetEl.textContent = opt.text;
                console.log(`${name} → "${opt.text}"`);
              } else {
                console.log(`– Placeholder ignoré pour ${name}`);
              }
              break;
        
            case "TEXTAREA":
              // pour garder les sauts de ligne dans ton template, si besoin on peut faire replace("\n","<br>")
              targetEl.textContent = srcEl.value;
              console.log(`${name} → "${srcEl.value}"`);
              break;
          }
        });
        
        // 6) Logs pour vérifier le DOM
        //console.log("wrapper.innerHTML →", wrapper.innerHTML);
        //console.log("wrapper.innerText →", wrapper.innerText);
        
        const filename = `${mode}-${postId}.pdf`;  // ex: "court_sejour-1950.pdf"

        // 7) Génération du PDF
        try {
          await html2pdf()
            .set({
              filename,
              image:       { type: "jpeg", quality: 0.98 },
              html2canvas: { scale: 2, useCORS: true },
              jsPDF:       { unit: "mm", format: "a4", orientation: "portrait" }
            })
            .from(wrapper)
            .save();
        
          console.log("PDF ‘test-template.pdf’ généré");
        } catch (err) {
          console.error("Erreur html2pdf:", err);
          alert("Échec de la génération PDF. Voir console.");
        } finally {
          // 8) Cleanup
          document.body.removeChild(wrapper);
        }
    });
});
