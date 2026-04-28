// =============================================
// EduSchedule Pro - Dashboard Enseignant
// =============================================

import { useAuth } from "../context/AuthContext"
import DashboardLayout from "../components/DashboardLayout"

export default function DashboardEnseignant() {
    const { utilisateur } = useAuth()

    return (
        <DashboardLayout>
            <div className="p-4">
                <div className="mb-4">
                    <h4 className="fw-bold" style={{ color: "#1a237e" }}>
                        📊 Tableau de bord Enseignant
                    </h4>
                    <p className="text-muted">
                        Bienvenue, {utilisateur?.email}
                    </p>
                </div>

                <div className="row g-3">
                    <div className="col-md-4">
                        <div className="card text-center p-4"
                            style={{ background: "#e8eaf6", border: "none", borderRadius: "12px" }}>
                            <div style={{ fontSize: "40px" }}>📅</div>
                            <h5 className="fw-bold mt-2" style={{ color: "#1a237e" }}>
                                Mes séances
                            </h5>
                            <p className="text-muted small">
                                Consultez vos créneaux de la semaine
                            </p>
                            <a href="/mes-seances" className="btn btn-sm"
                                style={{ background: "#1a237e", color: "white" }}>
                                Voir mes séances
                            </a>
                        </div>
                    </div>

                    <div className="col-md-4">
                        <div className="card text-center p-4"
                            style={{ background: "#e8f5e9", border: "none", borderRadius: "12px" }}>
                            <div style={{ fontSize: "40px" }}>📱</div>
                            <h5 className="fw-bold mt-2" style={{ color: "#1b5e20" }}>
                                Pointage QR
                            </h5>
                            <p className="text-muted small">
                                Scannez le QR-Code pour pointer
                            </p>
                            <a href="/pointage" className="btn btn-sm"
                                style={{ background: "#1b5e20", color: "white" }}>
                                Scanner QR-Code
                            </a>
                        </div>
                    </div>

                    <div className="col-md-4">
                        <div className="card text-center p-4"
                            style={{ background: "#fff3e0", border: "none", borderRadius: "12px" }}>
                            <div style={{ fontSize: "40px" }}>💰</div>
                            <h5 className="fw-bold mt-2" style={{ color: "#e65100" }}>
                                Mes vacations
                            </h5>
                            <p className="text-muted small">
                                Consultez vos fiches de vacation
                            </p>
                            <a href="/mes-vacations" className="btn btn-sm"
                                style={{ background: "#e65100", color: "white" }}>
                                Voir mes vacations
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    )
}