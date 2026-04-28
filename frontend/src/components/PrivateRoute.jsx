// =============================================
// EduSchedule Pro - Protection des routes
// =============================================

import { Navigate } from "react-router-dom"
import { useAuth } from "../context/AuthContext"

export default function PrivateRoute({ children, roles }) {
    const { utilisateur, token, loading } = useAuth()

    // Attendre le chargement
    if (loading) {
        return (
            <div className="d-flex justify-content-center 
                            align-items-center min-vh-100">
                <div className="spinner-border text-primary" role="status">
                    <span className="visually-hidden">Chargement...</span>
                </div>
            </div>
        )
    }

    // Non connecté → rediriger vers login
    if (!token || !utilisateur) {
        return <Navigate to="/login" replace />
    }

    // Rôle non autorisé → rediriger vers dashboard
    if (roles && !roles.includes(utilisateur.role)) {
        return <Navigate to="/dashboard" replace />
    }

    return children
}