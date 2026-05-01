// =============================================
// EduSchedule Pro - Page Cahier de Texte
// =============================================

import { useState, useEffect, useRef } from "react"
import axios from "axios"
import { useAuth } from "../context/AuthContext"
import DashboardLayout from "../components/DashboardLayout"
import SignatureCanvas from "react-signature-canvas"

export default function CahierTextePage() {
    const { token, utilisateur } = useAuth()
    const [cahiers, setCahiers] = useState([])
    const [creneaux, setCreneaux] = useState([])
    const [loading, setLoading] = useState(true)
    const [showModal, setShowModal] = useState(false)
    const [showSignModal, setShowSignModal] = useState(false)
    const [cahierSelect, setCahierSelect] = useState(null)
    const [message, setMessage] = useState(null)
    const signatureRef = useRef(null)

    const [nouveauCahier, setNouveauCahier] = useState({
        id_creneau: "",
        titre_cours: "",
        contenu: [""],
        travaux: []
    })

    const headers = { Authorization: `Bearer ${token}` }

    useEffect(() => {
        chargerCahiers()
    }, [])

    const chargerCahiers = async () => {
        setLoading(true)
        try {
            const res = await axios.get(
                "http://localhost/eduschedule-pro/backend/api/cahiers.php",
                { headers }
            )
            setCahiers(res.data.data)
        } catch (err) {
            console.error(err)
        } finally {
            setLoading(false)
        }
    }

    const handleCreerCahier = async () => {
        try {
            const res = await axios.post(
                "http://localhost/eduschedule-pro/backend/api/cahiers.php",
                {
                    id_creneau: nouveauCahier.id_creneau,
                    titre_cours: nouveauCahier.titre_cours,
                    contenu: nouveauCahier.contenu.filter(c => c.trim() !== ""),
                    travaux: nouveauCahier.travaux
                },
                { headers }
            )
            setMessage({ type: "success", text: "Cahier créé avec succès !" })
            setShowModal(false)
            chargerCahiers()
            setNouveauCahier({
                id_creneau: "",
                titre_cours: "",
                contenu: [""],
                travaux: []
            })
        } catch (err) {
            setMessage({
                type: "danger",
                text: err.response?.data?.message || "Erreur création cahier"
            })
        }
    }

    const handleSigner = async () => {
        if (signatureRef.current?.isEmpty()) {
            setMessage({ type: "warning", text: "Veuillez apposer votre signature !" })
            return
        }

        const signature_base64 = signatureRef.current.toDataURL()
        const type = utilisateur.role === "delegue" ? "delegue" : "enseignant"

        try {
            await axios.post(
                `http://localhost/eduschedule-pro/backend/api/cahiers.php?action=signer&id=${cahierSelect.id}`,
                { signature_base64, type },
                { headers }
            )
            setMessage({ type: "success", text: "Signature enregistrée avec succès !" })
            setShowSignModal(false)
            chargerCahiers()
        } catch (err) {
            setMessage({
                type: "danger",
                text: err.response?.data?.message || "Erreur signature"
            })
        }
    }

    const getStatutBadge = (statut) => {
        const badges = {
            brouillon: { color: "warning", label: "✏️ Brouillon" },
            signe_delegue: { color: "info", label: "✅ Signé délégué" },
            cloture: { color: "success", label: "🔒 Clôturé" }
        }
        return badges[statut] || { color: "secondary", label: statut }
    }

    const ajouterLigneContenu = () => {
        setNouveauCahier({
            ...nouveauCahier,
            contenu: [...nouveauCahier.contenu, ""]
        })
    }

    const modifierContenu = (index, valeur) => {
        const contenu = [...nouveauCahier.contenu]
        contenu[index] = valeur
        setNouveauCahier({ ...nouveauCahier, contenu })
    }

    const ajouterTravail = () => {
        setNouveauCahier({
            ...nouveauCahier,
            travaux: [...nouveauCahier.travaux, {
                description: "",
                date_limite: "",
                type: "devoir"
            }]
        })
    }

    const modifierTravail = (index, champ, valeur) => {
        const travaux = [...nouveauCahier.travaux]
        travaux[index][champ] = valeur
        setNouveauCahier({ ...nouveauCahier, travaux })
    }

    return (
        <DashboardLayout>
            <div className="p-4">

                {/* En-tête */}
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 className="fw-bold" style={{ color: "#1a237e" }}>
                            📝 Cahiers de Texte
                        </h4>
                        <p className="text-muted mb-0">
                            Suivi pédagogique des séances de cours
                        </p>
                    </div>
                    {["delegue", "admin"].includes(utilisateur?.role) && (
                        <button
                            className="btn text-white"
                            style={{ background: "#1a237e" }}
                            onClick={() => setShowModal(true)}
                        >
                            ➕ Nouveau cahier
                        </button>
                    )}
                </div>

                {/* Message */}
                {message && (
                    <div className={`alert alert-${message.type} alert-dismissible`}>
                        {message.text}
                        <button className="btn-close"
                            onClick={() => setMessage(null)}></button>
                    </div>
                )}

                {/* Liste des cahiers */}
                {loading ? (
                    <div className="text-center py-5">
                        <div className="spinner-border text-primary"></div>
                        <p className="mt-2 text-muted">Chargement...</p>
                    </div>
                ) : cahiers.length === 0 ? (
                    <div className="alert alert-info">
                        <strong>ℹ️</strong> Aucun cahier de texte trouvé.
                    </div>
                ) : (
                    <div className="row g-3">
                        {cahiers.map((cahier, i) => {
                            const badge = getStatutBadge(cahier.statut)
                            return (
                                <div key={i} className="col-md-6">
                                    <div className="card h-100">
                                        <div className="card-header d-flex justify-content-between align-items-center">
                                            <h6 className="mb-0 fw-bold">
                                                📚 {cahier.matiere}
                                            </h6>
                                            <span className={`badge bg-${badge.color}`}>
                                                {badge.label}
                                            </span>
                                        </div>
                                        <div className="card-body">
                                            <div className="mb-2">
                                                <strong>Titre :</strong> {cahier.titre_cours || "—"}
                                            </div>
                                            <div className="mb-2 small text-muted">
                                                📅 {cahier.jour} {cahier.heure_debut?.slice(0,5)} - {cahier.heure_fin?.slice(0,5)}
                                            </div>
                                            <div className="mb-2 small text-muted">
                                                👨‍🏫 {cahier.enseignant_prenom} {cahier.enseignant_nom}
                                            </div>
                                            <div className="mb-2 small text-muted">
                                                🎓 {cahier.classe}
                                            </div>
                                            <div className="mb-2 small text-muted">
                                                📅 {new Date(cahier.date_creation).toLocaleDateString('fr-FR')}
                                            </div>
                                        </div>
                                        <div className="card-footer d-flex gap-2">
                                            {cahier.statut !== "cloture" &&
                                                ["delegue", "enseignant"].includes(utilisateur?.role) && (
                                                <button
                                                    className="btn btn-sm btn-outline-primary flex-grow-1"
                                                    onClick={() => {
                                                        setCahierSelect(cahier)
                                                        setShowSignModal(true)
                                                    }}
                                                >
                                                    ✍️ Signer
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )
                        })}
                    </div>
                )}

                {/* Modal Nouveau Cahier */}
                {showModal && (
                    <div className="modal show d-block"
                        style={{ background: "rgba(0,0,0,0.5)" }}>
                        <div className="modal-dialog modal-lg">
                            <div className="modal-content">
                                <div className="modal-header"
                                    style={{ background: "#1a237e", color: "white" }}>
                                    <h5 className="modal-title">📝 Nouveau Cahier de Texte</h5>
                                    <button className="btn-close btn-close-white"
                                        onClick={() => setShowModal(false)}></button>
                                </div>
                                <div className="modal-body">

                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            ID Créneau
                                        </label>
                                        <input
                                            type="number"
                                            className="form-control"
                                            placeholder="ID du créneau pointé"
                                            value={nouveauCahier.id_creneau}
                                            onChange={e => setNouveauCahier({
                                                ...nouveauCahier,
                                                id_creneau: e.target.value
                                            })}
                                        />
                                    </div>

                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Titre du cours
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            placeholder="Ex: Chapitre 2 - Les bases de PHP"
                                            value={nouveauCahier.titre_cours}
                                            onChange={e => setNouveauCahier({
                                                ...nouveauCahier,
                                                titre_cours: e.target.value
                                            })}
                                        />
                                    </div>

                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Points vus dans le cours
                                        </label>
                                        {nouveauCahier.contenu.map((ligne, i) => (
                                            <input
                                                key={i}
                                                type="text"
                                                className="form-control mb-2"
                                                placeholder={`Point ${i + 1}`}
                                                value={ligne}
                                                onChange={e => modifierContenu(i, e.target.value)}
                                            />
                                        ))}
                                        <button
                                            className="btn btn-sm btn-outline-secondary"
                                            onClick={ajouterLigneContenu}
                                        >
                                            ➕ Ajouter un point
                                        </button>
                                    </div>

                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Travaux demandés
                                        </label>
                                        {nouveauCahier.travaux.map((t, i) => (
                                            <div key={i} className="card p-2 mb-2">
                                                <div className="row g-2">
                                                    <div className="col-md-6">
                                                        <input
                                                            type="text"
                                                            className="form-control form-control-sm"
                                                            placeholder="Description"
                                                            value={t.description}
                                                            onChange={e => modifierTravail(i, "description", e.target.value)}
                                                        />
                                                    </div>
                                                    <div className="col-md-3">
                                                        <input
                                                            type="date"
                                                            className="form-control form-control-sm"
                                                            value={t.date_limite}
                                                            onChange={e => modifierTravail(i, "date_limite", e.target.value)}
                                                        />
                                                    </div>
                                                    <div className="col-md-3">
                                                        <select
                                                            className="form-select form-select-sm"
                                                            value={t.type}
                                                            onChange={e => modifierTravail(i, "type", e.target.value)}
                                                        >
                                                            <option value="devoir">Devoir</option>
                                                            <option value="exercice">Exercice</option>
                                                            <option value="projet">Projet</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                        <button
                                            className="btn btn-sm btn-outline-secondary"
                                            onClick={ajouterTravail}
                                        >
                                            ➕ Ajouter un travail
                                        </button>
                                    </div>

                                </div>
                                <div className="modal-footer">
                                    <button className="btn btn-secondary"
                                        onClick={() => setShowModal(false)}>
                                        Annuler
                                    </button>
                                    <button
                                        className="btn text-white"
                                        style={{ background: "#1a237e" }}
                                        onClick={handleCreerCahier}
                                    >
                                        ✅ Créer le cahier
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Modal Signature */}
                {showSignModal && cahierSelect && (
                    <div className="modal show d-block"
                        style={{ background: "rgba(0,0,0,0.5)" }}>
                        <div className="modal-dialog">
                            <div className="modal-content">
                                <div className="modal-header"
                                    style={{ background: "#1a237e", color: "white" }}>
                                    <h5 className="modal-title">✍️ Signature Numérique</h5>
                                    <button className="btn-close btn-close-white"
                                        onClick={() => setShowSignModal(false)}></button>
                                </div>
                                <div className="modal-body">
                                    <p className="text-muted small mb-3">
                                        Apposez votre signature dans le cadre ci-dessous :
                                    </p>
                                    <div className="border rounded"
                                        style={{ background: "#f8f9fa" }}>
                                        <SignatureCanvas
                                            ref={signatureRef}
                                            canvasProps={{
                                                width: 460,
                                                height: 200,
                                                className: "signature-canvas"
                                            }}
                                        />
                                    </div>
                                    <button
                                        className="btn btn-sm btn-outline-secondary mt-2"
                                        onClick={() => signatureRef.current?.clear()}
                                    >
                                        🗑️ Effacer
                                    </button>
                                </div>
                                <div className="modal-footer">
                                    <button className="btn btn-secondary"
                                        onClick={() => setShowSignModal(false)}>
                                        Annuler
                                    </button>
                                    <button
                                        className="btn text-white"
                                        style={{ background: "#1a237e" }}
                                        onClick={handleSigner}
                                    >
                                        ✅ Valider la signature
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

            </div>
        </DashboardLayout>
    )
}