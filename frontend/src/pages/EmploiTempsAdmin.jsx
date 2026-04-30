// =============================================
// EduSchedule Pro - Emploi du Temps Admin
// =============================================

import { useState, useEffect } from "react"
import axios from "axios"
import { useAuth } from "../context/AuthContext"
import DashboardLayout from "../components/DashboardLayout"

const JOURS = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"]

export default function EmploiTempsAdmin() {
    const { token } = useAuth()
    const [classes, setClasses] = useState([])
    const [enseignants, setEnseignants] = useState([])
    const [matieres, setMatieres] = useState([])
    const [salles, setSalles] = useState([])
    const [creneaux, setCreneaux] = useState([])
    const [idClasseSelect, setIdClasseSelect] = useState("")
    const [semaine, setSemaine] = useState("2026-04-27")
    const [loading, setLoading] = useState(false)
    const [showModal, setShowModal] = useState(false)
    const [qrModal, setQrModal] = useState(null)
    const [nouveauCreneau, setNouveauCreneau] = useState({
        id_matiere: "",
        id_enseignant: "",
        id_salle: "",
        jour: "Lundi",
        heure_debut: "",
        heure_fin: ""
    })
    const [message, setMessage] = useState(null)

    const headers = { Authorization: `Bearer ${token}` }

    useEffect(() => {
        Promise.all([
            axios.get("http://localhost/eduschedule-pro/backend/api/classes.php", { headers }),
            axios.get("http://localhost/eduschedule-pro/backend/api/enseignants.php", { headers }),
            axios.get("http://localhost/eduschedule-pro/backend/api/matieres.php", { headers }),
            axios.get("http://localhost/eduschedule-pro/backend/api/salles.php", { headers })
        ]).then(([cl, ens, mat, sal]) => {
            setClasses(cl.data.data)
            setEnseignants(ens.data.data)
            setMatieres(mat.data.data)
            setSalles(sal.data.data)
            if (cl.data.data.length > 0) {
                setIdClasseSelect(cl.data.data[0].id)
            }
        }).catch(err => console.error(err))
    }, [])

    useEffect(() => {
        if (!idClasseSelect) return
        chargerCreneaux()
    }, [idClasseSelect, semaine])

    const chargerCreneaux = () => {
        setLoading(true)
        axios.get(
            `http://localhost/eduschedule-pro/backend/api/emploi_temps.php?action=creneaux&id_classe=${idClasseSelect}&semaine=${semaine}`,
            { headers }
        ).then(res => setCreneaux(res.data.data))
        .catch(err => console.error(err))
        .finally(() => setLoading(false))
    }

    const getCreneauxJour = (jour) => {
        return creneaux.filter(c => c.jour === jour)
    }

    const handleAjouterCreneau = async () => {
        try {
            const res = await axios.get(
                `http://localhost/eduschedule-pro/backend/api/emploi_temps.php?id_classe=${idClasseSelect}&semaine=${semaine}`,
                { headers }
            )

            if (res.data.data.length > 0) {
                setMessage({ type: "success", text: "Créneau ajouté avec succès !" })
                setShowModal(false)
                chargerCreneaux()
            } else {
                const create = await axios.post(
                    "http://localhost/eduschedule-pro/backend/api/emploi_temps.php",
                    {
                        id_classe: idClasseSelect,
                        semaine_debut: semaine,
                        creneaux: [nouveauCreneau]
                    },
                    { headers }
                )

                if (create.data.conflits?.length > 0) {
                    setMessage({
                        type: "danger",
                        text: "Conflit détecté : " + create.data.conflits.join(", ")
                    })
                    return
                }

                setMessage({ type: "success", text: "Créneau ajouté avec succès !" })
                setShowModal(false)
                chargerCreneaux()
            }
        } catch (err) {
            setMessage({ type: "danger", text: "Erreur lors de l'ajout du créneau" })
        }
    }

    // Générer et afficher le QR-Code
    const handleGenererQR = async (id_creneau) => {
        try {
            const res = await axios.get(
                `http://localhost/eduschedule-pro/backend/api/creneaux.php?action=qr&id=${id_creneau}`,
                { headers }
            )
            setQrModal(res.data)
        } catch (err) {
            setMessage({ type: "danger", text: "Erreur génération QR-Code" })
        }
    }

    // Supprimer un créneau
    const handleSupprimerCreneau = async (id_creneau) => {
        if (!window.confirm("Supprimer ce créneau ?")) return
        try {
            await axios.delete(
                "http://localhost/eduschedule-pro/backend/api/creneaux.php",
                {
                    headers,
                    data: { id: id_creneau }
                }
            )
            setMessage({ type: "success", text: "Créneau supprimé avec succès !" })
            chargerCreneaux()
        } catch (err) {
            setMessage({ type: "danger", text: "Erreur lors de la suppression" })
        }
    }

    return (
        <DashboardLayout>
            <div className="p-4">

                {/* En-tête */}
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 className="fw-bold" style={{ color: "#1a237e" }}>
                            📅 Gestion Emploi du Temps
                        </h4>
                        <p className="text-muted mb-0">
                            Créez et gérez les plannings hebdomadaires
                        </p>
                    </div>
                    <button
                        className="btn text-white"
                        style={{ background: "#1a237e" }}
                        onClick={() => setShowModal(true)}
                    >
                        ➕ Ajouter un créneau
                    </button>
                </div>

                {/* Message */}
                {message && (
                    <div className={`alert alert-${message.type} alert-dismissible`}>
                        {message.text}
                        <button className="btn-close"
                            onClick={() => setMessage(null)}></button>
                    </div>
                )}

                {/* Filtres */}
                <div className="card mb-4">
                    <div className="card-body">
                        <div className="row g-3">
                            <div className="col-md-5">
                                <label className="form-label fw-semibold">🎓 Classe</label>
                                <select className="form-select"
                                    value={idClasseSelect}
                                    onChange={e => setIdClasseSelect(e.target.value)}>
                                    {classes.map(cl => (
                                        <option key={cl.id} value={cl.id}>
                                            {cl.libelle}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="col-md-4">
                                <label className="form-label fw-semibold">📆 Semaine du</label>
                                <input type="date" className="form-control"
                                    value={semaine}
                                    onChange={e => setSemaine(e.target.value)} />
                            </div>
                            <div className="col-md-3 d-flex align-items-end">
                                <span className="badge bg-primary p-2">
                                    {creneaux.length} créneaux
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Grille emploi du temps */}
                {loading ? (
                    <div className="text-center py-5">
                        <div className="spinner-border text-primary"></div>
                    </div>
                ) : (
                    <div className="row g-3">
                        {JOURS.map((jour, i) => (
                            <div key={i} className="col-md-4 col-sm-6">
                                <div className="card h-100">
                                    <div className="card-header py-2">
                                        <h6 className="mb-0 fw-bold">📆 {jour}</h6>
                                    </div>
                                    <div className="card-body p-2">
                                        {getCreneauxJour(jour).length === 0 ? (
                                            <div className="text-center text-muted py-3 small">
                                                Aucun cours
                                            </div>
                                        ) : (
                                            getCreneauxJour(jour).map((c, j) => (
                                                <div key={j} className="creneau-card mb-2">
                                                    <div className="fw-bold small text-primary">
                                                        {c.matiere_libelle}
                                                    </div>
                                                    <div className="small text-muted">
                                                        🕐 {c.heure_debut?.slice(0,5)} - {c.heure_fin?.slice(0,5)}
                                                    </div>
                                                    <div className="small text-muted">
                                                        👨‍🏫 {c.enseignant_prenom} {c.enseignant_nom}
                                                    </div>
                                                    <div className="small text-muted">
                                                        🏫 {c.salle_code}
                                                    </div>
                                                    <div className="d-flex gap-1 mt-1">
                                                        <button
                                                            className="btn btn-sm btn-outline-primary flex-grow-1"
                                                            onClick={() => handleGenererQR(c.id)}
                                                        >
                                                            📱 QR-Code
                                                        </button>
                                                        <button
                                                            className="btn btn-sm btn-outline-danger"
                                                            onClick={() => handleSupprimerCreneau(c.id)}
                                                        >
                                                            🗑️
                                                        </button>
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Modal Ajouter Créneau */}
                {showModal && (
                    <div className="modal show d-block" style={{ background: "rgba(0,0,0,0.5)" }}>
                        <div className="modal-dialog">
                            <div className="modal-content">
                                <div className="modal-header"
                                    style={{ background: "#1a237e", color: "white" }}>
                                    <h5 className="modal-title">➕ Nouveau Créneau</h5>
                                    <button className="btn-close btn-close-white"
                                        onClick={() => setShowModal(false)}></button>
                                </div>
                                <div className="modal-body">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">Jour</label>
                                        <select className="form-select"
                                            value={nouveauCreneau.jour}
                                            onChange={e => setNouveauCreneau({
                                                ...nouveauCreneau, jour: e.target.value
                                            })}>
                                            {JOURS.map(j => (
                                                <option key={j} value={j}>{j}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">Matière</label>
                                        <select className="form-select"
                                            value={nouveauCreneau.id_matiere}
                                            onChange={e => setNouveauCreneau({
                                                ...nouveauCreneau, id_matiere: e.target.value
                                            })}>
                                            <option value="">-- Choisir --</option>
                                            {matieres.map(m => (
                                                <option key={m.id} value={m.id}>
                                                    {m.libelle}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">Enseignant</label>
                                        <select className="form-select"
                                            value={nouveauCreneau.id_enseignant}
                                            onChange={e => setNouveauCreneau({
                                                ...nouveauCreneau, id_enseignant: e.target.value
                                            })}>
                                            <option value="">-- Choisir --</option>
                                            {enseignants.map(e => (
                                                <option key={e.id} value={e.id}>
                                                    {e.prenom} {e.nom}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">Salle</label>
                                        <select className="form-select"
                                            value={nouveauCreneau.id_salle}
                                            onChange={e => setNouveauCreneau({
                                                ...nouveauCreneau, id_salle: e.target.value
                                            })}>
                                            <option value="">-- Choisir --</option>
                                            {salles.map(s => (
                                                <option key={s.id} value={s.id}>
                                                    {s.code} (cap. {s.capacite})
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="row g-2">
                                        <div className="col-6">
                                            <label className="form-label fw-semibold">
                                                Heure début
                                            </label>
                                            <input type="time" className="form-control"
                                                value={nouveauCreneau.heure_debut}
                                                onChange={e => setNouveauCreneau({
                                                    ...nouveauCreneau,
                                                    heure_debut: e.target.value
                                                })} />
                                        </div>
                                        <div className="col-6">
                                            <label className="form-label fw-semibold">
                                                Heure fin
                                            </label>
                                            <input type="time" className="form-control"
                                                value={nouveauCreneau.heure_fin}
                                                onChange={e => setNouveauCreneau({
                                                    ...nouveauCreneau,
                                                    heure_fin: e.target.value
                                                })} />
                                        </div>
                                    </div>
                                </div>
                                <div className="modal-footer">
                                    <button className="btn btn-secondary"
                                        onClick={() => setShowModal(false)}>
                                        Annuler
                                    </button>
                                    <button className="btn text-white"
                                        style={{ background: "#1a237e" }}
                                        onClick={handleAjouterCreneau}>
                                        ✅ Ajouter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Modal QR-Code */}
                {qrModal && (
                    <div className="modal show d-block" style={{ background: "rgba(0,0,0,0.5)" }}>
                        <div className="modal-dialog modal-sm">
                            <div className="modal-content">
                                <div className="modal-header"
                                    style={{ background: "#1a237e", color: "white" }}>
                                    <h5 className="modal-title">📱 QR-Code Séance</h5>
                                    <button className="btn-close btn-close-white"
                                        onClick={() => setQrModal(null)}></button>
                                </div>
                                <div className="modal-body text-center">
                                    <div className="mb-3">
                                        <div className="fw-bold text-primary">
                                            {qrModal.creneau?.matiere}
                                        </div>
                                        <div className="small text-muted">
                                            {qrModal.creneau?.classe}
                                        </div>
                                        <div className="small text-muted">
                                            {qrModal.creneau?.jour} {qrModal.creneau?.heure_debut?.slice(0,5)} - {qrModal.creneau?.heure_fin?.slice(0,5)}
                                        </div>
                                        <div className="small text-muted">
                                            👨‍🏫 {qrModal.creneau?.enseignant}
                                        </div>
                                    </div>
                                    <img
                                        src={qrModal.qr_image}
                                        alt="QR Code"
                                        className="img-fluid border rounded p-2"
                                        style={{ maxWidth: "200px" }}
                                    />
                                    <div className="mt-2 small text-muted">
                                        ⏰ Expire à {qrModal.qr_expire?.slice(11,16)}
                                    </div>
                                </div>
                                <div className="modal-footer">
                                    <button className="btn btn-secondary btn-sm"
                                        onClick={() => setQrModal(null)}>
                                        Fermer
                                    </button>
                                    <a href={qrModal.qr_image}
                                        download="qrcode-seance.svg"
                                        className="btn btn-sm text-white"
                                        style={{ background: "#1a237e" }}>
                                        ⬇️ Télécharger
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

            </div>
        </DashboardLayout>
    )
}