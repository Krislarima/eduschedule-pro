// =============================================
// EduSchedule Pro - Dashboard Délégué
// =============================================

import { useAuth } from "../context/AuthContext"
import DashboardLayout from "../components/DashboardLayout"

export default function DashboardDelegue() {
    const { utilisateur } = useAuth()

    return (
        <DashboardLayout>
            <div className="p-4">
                <div className="mb-4">
                    <h4 className="fw-bold" style={{ color: "#1a237e" }}>
                        📊 Tableau de bord Délégué
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
                                Emploi du temps
                            </h5>
                            <p className="text-muted small">
                                Consultez le planning de votre classe
                            </p>
                            <a href="/emploi-temps" className="btn btn-sm"
                                style={{ background: "#1a237e", color: "white" }}>
                                Voir le planning
                            </a>
                        </div>
                    </div>

                    <div className="col-md-4">
                        <div className="card text-center p-4"
                            style={{ background: "#e8f5e9", border: "none", borderRadius: "12px" }}>
                            <div style={{ fontSize: "40px" }}>📝</div>
                            <h5 className="fw-bold mt-2" style={{ color: "#1b5e20" }}>
                                Cahiers de texte
                            </h5>
                            <p className="text-muted small">
                                Remplissez les cahiers de texte
                            </p>
                            <a href="/cahiers" className="btn btn-sm"
                                style={{ background: "#1b5e20", color: "white" }}>
                                Gérer les cahiers
                            </a>
                        </div>
                    </div>

                    <div className="col-md-4">
                        <div className="card text-center p-4"
                            style={{ background: "#fff3e0", border: "none", borderRadius: "12px" }}>
                            <div style={{ fontSize: "40px" }}>📜</div>
                            <h5 className="fw-bold mt-2" style={{ color: "#e65100" }}>
                                Historique
                            </h5>
                            <p className="text-muted small">
                                Consultez les cahiers signés
                            </p>
                            <a href="/cahiers" className="btn btn-sm"
                                style={{ background: "#e65100", color: "white" }}>
                                Voir l'historique
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    )
}