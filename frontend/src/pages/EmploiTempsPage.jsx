// =============================================
// EduSchedule Pro - Page Emploi du Temps
// =============================================

import { useState, useEffect } from "react"
import axios from "axios"
import { useAuth } from "../context/AuthContext"
import DashboardLayout from "../components/DashboardLayout"

const JOURS = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"]

const COULEURS = [
    "#1a237e", "#1b5e20", "#e65100", "#4a148c",
    "#006064", "#b71c1c", "#f57f17", "#0d47a1"
]

export default function EmploiTempsPage() {
    const { token } = useAuth()
    const [classes, setClasses] = useState([])
    const [creneaux, setCreneaux] = useState([])
    const [idClasseSelect, setIdClasseSelect] = useState("")
    const [semaine, setSemaine] = useState("2026-04-27")
    const [loading, setLoading] = useState(false)

    const headers = { Authorization: `Bearer ${token}` }

    // Charger les classes
    useEffect(() => {
        axios.get(
            "http://localhost/eduschedule-pro/backend/api/classes.php",
            { headers }
        ).then(res => {
            setClasses(res.data.data)
            if (res.data.data.length > 0) {
                setIdClasseSelect(res.data.data[0].id)
            }
        }).catch(err => console.error(err))
    }, [])

    // Charger les créneaux quand classe ou semaine change
    useEffect(() => {
        if (!idClasseSelect) return
        setLoading(true)
        axios.get(
            `http://localhost/eduschedule-pro/backend/api/emploi_temps.php?action=creneaux&id_classe=${idClasseSelect}&semaine=${semaine}`,
            { headers }
        ).then(res => {
            setCreneaux(res.data.data)
        }).catch(err => console.error(err))
        .finally(() => setLoading(false))
    }, [idClasseSelect, semaine])

    // Obtenir les créneaux d'un jour
    const getCreneauxJour = (jour) => {
        return creneaux.filter(c => c.jour === jour)
    }

    // Couleur par matière
    const getCouleurMatiere = (idMatiere) => {
        return COULEURS[idMatiere % COULEURS.length]
    }

    return (
        <DashboardLayout>
            <div className="p-4">

                {/* En-tête */}
                <div className="mb-4">
                    <h4 className="fw-bold" style={{ color: "#1a237e" }}>
                        📅 Emploi du Temps
                    </h4>
                    <p className="text-muted">
                        Consultez le planning hebdomadaire par classe
                    </p>
                </div>

                {/* Filtres */}
                <div className="card mb-4">
                    <div className="card-body">
                        <div className="row g-3 align-items-end">
                            <div className="col-md-4">
                                <label className="form-label fw-semibold">
                                    🎓 Classe
                                </label>
                                <select
                                    className="form-select"
                                    value={idClasseSelect}
                                    onChange={e => setIdClasseSelect(e.target.value)}
                                >
                                    {classes.map(cl => (
                                        <option key={cl.id} value={cl.id}>
                                            {cl.libelle}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="col-md-4">
                                <label className="form-label fw-semibold">
                                    📆 Semaine du
                                </label>
                                <input
                                    type="date"
                                    className="form-control"
                                    value={semaine}
                                    onChange={e => setSemaine(e.target.value)}
                                />
                            </div>
                            <div className="col-md-4">
                                <div className="d-flex gap-2">
                                    <span className="badge bg-success p-2">
                                        {creneaux.length} créneaux
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Grille emploi du temps */}
                {loading ? (
                    <div className="text-center py-5">
                        <div className="spinner-border text-primary"></div>
                        <p className="mt-2 text-muted">Chargement...</p>
                    </div>
                ) : (
                    <div className="row g-3">
                        {JOURS.map((jour, i) => (
                            <div key={i} className="col-md-4 col-sm-6">
                                <div className="card h-100">
                                    <div className="card-header py-2">
                                        <h6 className="mb-0 fw-bold">
                                            📆 {jour}
                                        </h6>
                                    </div>
                                    <div className="card-body p-2">
                                        {getCreneauxJour(jour).length === 0 ? (
                                            <div className="text-center text-muted py-3 small">
                                                Aucun cours
                                            </div>
                                        ) : (
                                            getCreneauxJour(jour).map((c, j) => (
                                                <div key={j} className="creneau-card mb-2"
                                                    style={{
                                                        borderLeftColor: getCouleurMatiere(c.id_matiere)
                                                    }}>
                                                    <div className="fw-bold small"
                                                        style={{ color: getCouleurMatiere(c.id_matiere) }}>
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
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Message si aucun créneau */}
                {!loading && creneaux.length === 0 && (
                    <div className="alert alert-info mt-3">
                        <strong>ℹ️ Aucun créneau</strong> — 
                        Aucun emploi du temps n'a été publié pour cette classe 
                        et cette semaine.
                    </div>
                )}

            </div>
        </DashboardLayout>
    )
}