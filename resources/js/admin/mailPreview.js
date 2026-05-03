const CONTACT_COPY = {
    quote: {
        badge: "Suivi devis",
        statusBadges: {
            in_progress: "Devis en etude",
            quoted: "Devis pret",
            completed: "Devis finalise",
            cancelled: "Devis cloture",
            default: "Suivi devis",
        },
        statusTitles: {
            in_progress: "Votre devis est en cours d'etude",
            quoted: "Votre devis est pret",
            completed: "Votre devis est finalise",
            cancelled: "Votre devis est cloture",
            default: "Suivi de votre devis",
        },
        statusSubjects: {
            in_progress: "Votre devis #{{reference}} est en cours d'etude",
            quoted: "Votre devis #{{reference}} est pret",
            completed: "Votre devis #{{reference}} est finalise",
            cancelled: "Votre devis #{{reference}} a ete cloture",
            default: "Suivi de votre devis #{{reference}}",
        },
        statusIntros: {
            in_progress:
                "Votre demande de devis est maintenant en cours de traitement par notre equipe.",
            quoted: "Votre dossier a avance jusqu'a l'etape devis envoye.",
            completed: "Votre demande de devis est maintenant cloturee.",
            cancelled: "Votre demande de devis a ete annulee ou classee sans suite.",
            default: "Votre demande de devis a fait l'objet d'une mise a jour.",
        },
        statusNextSteps: {
            in_progress:
                "Nous analysons maintenant votre besoin, les contraintes de date, de volume et les arbitrages utiles avant notre retour.",
            quoted: "Si un point manque ou si vous souhaitez ajuster le perimetre, repondez directement a cet email pour que nous puissions affiner la suite.",
            completed:
                "Le dossier est considere comme finalise. Nous restons disponibles si vous souhaitez relancer un besoin complementaire.",
            cancelled:
                "Si vous souhaitez reouvrir le sujet ou repartir sur une autre base, vous pouvez simplement repondre a ce message.",
            default: "Nous vous tenons informes des prochaines etapes sur votre demande de devis.",
        },
        statusSummaries: {
            in_progress:
                "Votre devis est en cours d'analyse avec vos contraintes et votre selection.",
            quoted: "Votre dossier est passe au stade devis envoye et reste ouvert a vos ajustements.",
            completed: "Le cycle de devis est maintenant clos sur votre dossier.",
            cancelled:
                "Le dossier a ete ferme, avec possibilite de repartir sur une nouvelle demande.",
            default: "Votre demande de devis vient d'etre mise a jour.",
        },
        statusMessages: {
            in_progress: "Notre equipe est en train d'etudier votre dossier.",
            quoted: "Votre dossier est a l'etape devis envoye. Si un element manque dans nos echanges, dites-le-nous et nous vous repondrons rapidement.",
            completed: "Le suivi de votre dossier est maintenant termine.",
            cancelled: "Le dossier est actuellement classe comme annule.",
            default: "Votre demande de devis a ete mise a jour.",
        },
        statusClosings: {
            quoted: "Conservez bien cette reference et revenez vers nous si vous souhaitez arbitrer une quantite, une composition ou le format de service.",
            cancelled:
                "Si le contexte change, nous pourrons reprendre votre besoin a partir de cet historique.",
            default:
                "Vous pouvez continuer a nous preciser vos attentes en reponse a cet email pour accelerer un cadrage propre.",
        },
    },
    contact: {
        badge: "Suivi contact",
        statusBadges: {
            in_progress: "Demande en etude",
            quoted: "Proposition prete",
            completed: "Demande finalisee",
            cancelled: "Demande cloturee",
            default: "Suivi demande",
        },
        statusTitles: {
            in_progress: "Votre demande est en cours d'etude",
            quoted: "Une proposition est prete",
            completed: "Votre demande est finalisee",
            cancelled: "Votre demande est cloturee",
            default: "Suivi de votre demande",
        },
        statusSubjects: {
            in_progress: "Nous etudions votre demande #{{reference}}",
            quoted: "Une proposition est prete pour votre demande #{{reference}}",
            completed: "Votre demande #{{reference}} est finalisee",
            cancelled: "Votre demande #{{reference}} a ete cloturee",
            default: "Suivi de votre demande #{{reference}}",
        },
        statusIntros: {
            in_progress:
                "Votre prise de contact est maintenant en cours de traitement par notre equipe.",
            quoted: "Votre dossier a avance jusqu'a l'etape devis envoye.",
            completed: "Votre demande est maintenant cloturee.",
            cancelled: "Votre demande a ete annulee ou classee sans suite.",
            default: "Votre demande a fait l'objet d'une mise a jour.",
        },
        statusNextSteps: {
            in_progress:
                "Nous analysons maintenant votre besoin, les contraintes de date, de volume et les arbitrages utiles avant notre retour.",
            quoted: "Si un point manque ou si vous souhaitez ajuster le perimetre, repondez directement a cet email pour que nous puissions affiner la suite.",
            completed:
                "Le dossier est considere comme finalise. Nous restons disponibles si vous souhaitez relancer un besoin complementaire.",
            cancelled:
                "Si vous souhaitez reouvrir le sujet ou repartir sur une autre base, vous pouvez simplement repondre a ce message.",
            default: "Nous vous tenons informes de la suite donnee a votre prise de contact.",
        },
        statusSummaries: {
            in_progress: "Votre demande est maintenant en cours de reprise par notre equipe.",
            quoted: "Le dossier a bascule sur l'etape devis envoye.",
            completed: "Votre demande est consideree comme finalisee.",
            cancelled: "Le dossier a ete ferme, avec possibilite de reprendre le sujet ensuite.",
            default: "Votre prise de contact vient d'etre mise a jour.",
        },
        statusMessages: {
            in_progress: "Notre equipe est en train d'etudier votre dossier.",
            quoted: "Votre dossier est a l'etape devis envoye. Si un element manque dans nos echanges, dites-le-nous et nous vous repondrons rapidement.",
            completed: "Le suivi de votre dossier est maintenant termine.",
            cancelled: "Le dossier est actuellement classe comme annule.",
            default: "Votre demande a ete mise a jour.",
        },
        statusClosings: {
            cancelled:
                "Si le contexte change, nous pourrons reprendre votre besoin a partir de cet historique.",
            default:
                "Vous pouvez repondre a cet email si vous devez ajouter une precision utile a notre equipe.",
        },
    },
};

const ORDER_COPY = {
    badge: "Suivi commande",
    statusBadges: {
        confirmed: "Commande confirmee",
        preparing: "Preparation en cours",
        ready: "Commande prete",
        completed: "Commande finalisee",
        cancelled: "Commande annulee",
        default: "Suivi commande",
    },
    statusTitles: {
        confirmed: "Votre commande est confirmee",
        preparing: "Votre commande est en preparation",
        ready: "Votre commande est prete",
        completed: "Votre commande est finalisee",
        cancelled: "Votre commande est annulee",
        default: "Suivi de votre commande",
    },
    statusSubjects: {
        confirmed: "Votre commande #{{reference}} est confirmee",
        preparing: "Votre commande #{{reference}} est en preparation",
        ready: "Votre commande #{{reference}} est prete",
        completed: "Votre commande #{{reference}} est finalisee",
        cancelled: "Votre commande #{{reference}} a ete annulee",
        default: "Suivi de votre commande #{{reference}}",
    },
    statusIntros: {
        confirmed: "Votre commande a bien ete confirmee par notre equipe.",
        preparing: "Votre commande est maintenant en preparation.",
        ready: "Votre commande est maintenant prete.",
        completed: "Votre commande est maintenant finalisee.",
        cancelled: "Votre commande a ete annulee.",
        default: "Votre commande a fait l'objet d'une mise a jour.",
    },
    statusNextSteps: {
        confirmed:
            "Nous conservons votre demande dans notre planning et vous recontactons si un ajustement logistique est necessaire.",
        preparing:
            "Notre equipe avance maintenant sur la preparation et le bon deroulement du retrait ou de la livraison.",
        ready:
            "Votre commande est prete. Vous pouvez venir sur le creneau prevu ou nous repondre directement si un ajustement est necessaire.",
        completed:
            "Le dossier est considere comme boucle. Si vous avez besoin d'un nouveau retrait ou d'une nouvelle commande, vous pouvez nous recontacter librement.",
        cancelled:
            "Si vous souhaitez relancer la commande sur une autre date ou un autre format, repondez simplement a cet email.",
        default: "Nous vous tenons informes de la suite donnee a votre commande.",
    },
    statusSummaries: {
        confirmed: "Votre commande est confirmee et integree dans notre suivi.",
        preparing: "Votre commande est entree dans le flux de preparation.",
        ready: "Votre commande est terminee et attend maintenant son retrait ou sa remise.",
        completed: "Le cycle de votre commande est maintenant clos.",
        cancelled: "La commande a ete fermee avec statut annule.",
        default: "Votre commande vient d'etre mise a jour.",
    },
    statusMessages: {
        confirmed: "Votre commande est bien prise en charge.",
        preparing: "Notre equipe est en train de preparer votre commande.",
        ready: "Votre commande est prete et peut maintenant etre retiree ou remise selon l'organisation prevue.",
        completed: "Votre commande est marquee comme finalisee.",
        cancelled: "Votre commande est actuellement classee comme annulee.",
        default: "Votre commande a ete mise a jour.",
    },
    statusClosings: {
        cancelled:
            "Nous restons disponibles si vous souhaitez repartir sur une nouvelle commande ou une autre date.",
        ready:
            "Conservez cette reference et repondez directement a cet email si vous devez nous prevenir d'un retard ou d'un ajustement de retrait.",
        completed:
            "Merci pour votre confiance. Vous pouvez repondre a cet email si vous souhaitez preparer une prochaine commande.",
        default:
            "Conservez cette reference de commande. Vous pouvez repondre directement a cet email si un point doit etre precise avant le retrait ou la livraison.",
    },
};

function parseStatusLabels(value) {
    try {
        return JSON.parse(value || "{}");
    } catch {
        return {};
    }
}

function pick(map, status) {
    return map[status] || map.default || "";
}

function lineBreaks(text) {
    return String(text || "").replace(/\n/g, "<br>");
}

function interpolate(template, reference) {
    return String(template || "").replace(/\{\{reference\}\}/g, reference);
}

function buildSubject(kind, reference, status) {
    const copy = getCopy(kind);
    const subject = interpolate(pick(copy.statusSubjects || {}, status), reference);
    return `Traiteur Passion - ${subject}`;
}

function getCopy(kind) {
    if (kind === "order") {
        return ORDER_COPY;
    }

    return CONTACT_COPY[kind] || CONTACT_COPY.contact;
}

export function initAdminMailPreview() {
    const roots = document.querySelectorAll("[data-mail-preview-root]");
    if (!roots.length) {
        return;
    }

    roots.forEach((root) => {
        const form = root.closest("form");
        if (!form) {
            return;
        }

        const kind = root.getAttribute("data-mail-kind") || "contact";
        const reference = root.getAttribute("data-reference") || "0";
        const clientName = root.getAttribute("data-client-name") || "Client";
        const statusLabels = parseStatusLabels(root.getAttribute("data-status-labels"));
        const statusSelect = form.querySelector('select[name="status"]');
        const notifyInput = form.querySelector('input[name="notify_client"]');
        const subjectInput = form.querySelector("[data-mail-subject]");
        const messageInput = form.querySelector("[data-mail-message]");
        const subjectReset = form.querySelector("[data-mail-subject-reset]");

        if (!statusSelect || !notifyInput || !subjectInput || !messageInput) {
            return;
        }

        const preview = {
            badge: root.querySelector("[data-mail-preview-badge]"),
            title: root.querySelector("[data-mail-preview-title]"),
            summary: root.querySelector("[data-mail-preview-summary]"),
            greeting: root.querySelector("[data-mail-preview-greeting]"),
            subject: root.querySelector("[data-mail-preview-subject]"),
            intro: root.querySelector("[data-mail-preview-intro]"),
            message: root.querySelector("[data-mail-preview-message]"),
            nextStep: root.querySelector("[data-mail-preview-next-step]"),
            closing: root.querySelector("[data-mail-preview-closing]"),
        };

        let subjectManuallyEdited = false;

        const refresh = () => {
            const status = statusSelect.value;
            const copy = getCopy(kind);
            const defaultSubject = buildSubject(kind, reference, status);
            const defaultMessage = pick(copy.statusMessages, status);
            const customMessage = String(messageInput.value || "").trim();

            if (!subjectManuallyEdited || String(subjectInput.value || "").trim() === "") {
                subjectInput.value = defaultSubject;
            }

            if (preview.badge) {
                preview.badge.textContent = pick(copy.statusBadges || {}, status) || copy.badge;
            }
            if (preview.title) {
                preview.title.textContent = pick(copy.statusTitles || {}, status);
            }
            if (preview.summary) {
                preview.summary.textContent = pick(copy.statusSummaries, status);
            }
            if (preview.greeting) {
                preview.greeting.textContent = `Bonjour ${clientName},`;
            }
            if (preview.subject) {
                preview.subject.textContent = subjectInput.value;
            }
            if (preview.intro) {
                preview.intro.textContent = pick(copy.statusIntros, status);
            }
            if (preview.message) {
                preview.message.innerHTML = lineBreaks(
                    customMessage === ""
                        ? defaultMessage
                        : `${defaultMessage}\n\nMessage de notre equipe\n${customMessage}`,
                );
            }
            if (preview.nextStep) {
                preview.nextStep.textContent = pick(copy.statusNextSteps, status);
            }
            if (preview.closing) {
                preview.closing.textContent = pick(copy.statusClosings, status);
            }

            root.classList.toggle("is-disabled", !notifyInput.checked);
        };

        subjectInput.addEventListener("input", () => {
            subjectManuallyEdited = true;
            refresh();
        });

        messageInput.addEventListener("input", refresh);
        statusSelect.addEventListener("change", refresh);
        notifyInput.addEventListener("change", refresh);

        if (subjectReset) {
            subjectReset.addEventListener("click", () => {
                subjectManuallyEdited = false;
                refresh();
            });
        }

        refresh();
    });
}
