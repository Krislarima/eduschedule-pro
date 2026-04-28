// =============================================
// EduSchedule Pro - Dashboard Administrateur
// =============================================

import { useState, useEffect } from "react"
import axios from "axios"
import { useAuth } from "../context/AuthContext"
import DashboardLayout from "../components/DashboardLayout"

export default function DashboardAdmin() {
    const { token } = useAuth()
    const [stats, setStats] = useState({
        classes: 0,
        enseignants: 0,
        matieres: 0,
        salles: 0
    })
    const [loading, setLoading] = useState(true)

    const headers = { Authorization: `Bearer ${token}` }

    useEffect(() => {
        const fetchStats = async () => {
            try {
                const [classes, enseignants, matieres, salles] = await Promise.all([
                    axios.get("http://localhost/eduschedule-pro/backend/api/classes.php", { headers }),
                    axios.get("http://localhost/eduschedule-pro/backend/api/enseignants.php", { headers }),
                    axios.get("http://localhost/eduschedule-pro/backend/api/matieres.php", { headers }),
                    axios.get("http://localhost/eduschedule-pro/backend/api/salles.php", { headers })
                ])
                setStats({
                    classes: classes.data.total,
                    enseignants: enseignants.data.total,
                    matieres: matieres.data.total,
                    salles: salles.data.total
                })
            } catch (err) {
                console.error("Erreur chargement stats:", err)
            } finally {
                setLoading(false)
            }
        }
        fetchStats()
    }, [])

    const cartes = [
        { icon: "🎓", label: "Classes", valeur: stats.classes, couleur: "#1a237e", bg: "#e8eaf6" },
        { icon: "👨‍🏫", label: "Enseignants", valeur: stats.enseignants, couleur: "#1b5e20", bg: "#e8f5e9" },
        { icon: "📚", label: "Matières", valeur: stats.matieres, couleur: "#e65100", bg: "#fff3e0" },
        { icon: "🏫", label: "Salles", valeur: stats.salles, couleur: "#4a148c", bg: "#f3e5f5" },
    ]

    return (
        <DashboardLayout>
            <div className="p-4">

                {/* En-tête */}
                <div className="mb-4">
                    <h4 className="fw-bold" style={{ color: "#1a237e" }}>
                        📊 Tableau de bord Administrateur
                    </h4>
                    <p className="text-muted">
                        Vue d'ensemble de la plateforme EduSchedule Pro
                    </p>
                </div>

                {/* Cartes statistiques */}
                {loading ? (
                    <div className="text-center py-5">
                        <div className="spinner-border text-primary"></div>
                        <p className="mt-2 text-muted">Chargement...</p>
                    </div>
                ) : (
                    <div className="row g-3 mb-4">
                        {cartes.map((carte, i) => (
                            <div key={i} className="col-md-3 col-sm-6">
                                <div className="card h-100">
                                    <div className="card-body d-flex align-items-center gap-3"
                                        style={{ background: carte.bg, borderRadius: "12px" }}>
                                        <div style={{ fontSize: "40px" }}>{carte.icon}</div>
                                        <div>
                                            <div className="fw-bold fs-2"
                                                style={{ color: carte.couleur }}>
                                                {carte.valeur}
                                            </div>
                                            <div className="text-muted small fw-semibold">
                                                {carte.label}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Actions rapides */}
                <div className="row g-3">
                    <div className="col-md-6">
                        <div className="card">
                            <div className="card-header">
                                ⚡ Actions rapides
                            </div>
                            <div className="card-body">
                                <div className="d-grid gap-2">
                                    <a href="/emploi-temps"
                                        className="btn btn-outline-primary text-start">
                                        📅 Gérer les emplois du temps
                                    </a>
                                    <a href="/enseignants"
                                        className="btn btn-outline-success text-start">
                                        👨‍🏫 Gérer les enseignants
                                    </a>
                                    <a href="/classes"
                                        className="btn btn-outline-warning text-start">
                                        🎓 Gérer les classes
                                    </a>
                                    <a href="/pointages"
                                        className="btn btn-outline-info text-start">
                                        📋 Voir les pointages
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="col-md-6">
                        <div className="card">
                            <div className="card-header">
                                ℹ️ Informations système
                            </div>
                            <div className="card-body">
                                <table className="table table-sm">
                                    <tbody>
                                        <tr>
                                            <td className="text-muted">Application</td>
                                            <td className="fw-semibold">EduSchedule Pro</td>
                                        </tr>
                                        <tr>
                                            <td className="text-muted">Établissement</td>
                                            <td className="fw-semibold">ISGE-BF</td>
                                        </tr>
                                        <tr>
                                            <td className="text-muted">Année académique</td>
                                            <td className="fw-semibold">2025-2026</td>
                                        </tr>
                                        <tr>
                                            <td className="text-muted">Version</td>
                                            <td className="fw-semibold">1.0.0</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </DashboardLayout>
    )
}