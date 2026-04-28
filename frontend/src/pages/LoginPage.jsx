// =============================================
// EduSchedule Pro - Page de Connexion
// =============================================

import { useState } from "react"
import { useNavigate } from "react-router-dom"
import axios from "axios"
import { useAuth } from "../context/AuthContext"

export default function LoginPage() {
    const [email, setEmail] = useState("")
    const [password, setPassword] = useState("")
    const [erreur, setErreur] = useState("")
    const [loading, setLoading] = useState(false)

    const { login } = useAuth()
    const navigate = useNavigate()

    const handleSubmit = async (e) => {
        e.preventDefault()
        setErreur("")
        setLoading(true)

        try {
            const response = await axios.post(
                "http://localhost/eduschedule-pro/backend/api/auth.php",
                { email, password }
            )

            if (response.data.success) {
                login(response.data.utilisateur, response.data.token)

                // Redirection selon le rôle
                const role = response.data.utilisateur.role
                if (role === "admin") navigate("/dashboard/admin")
                else if (role === "enseignant") navigate("/dashboard/enseignant")
                else if (role === "delegue") navigate("/dashboard/delegue")
                else if (role === "surveillant") navigate("/dashboard/surveillant")
                else if (role === "comptable") navigate("/dashboard/comptable")
                else navigate("/dashboard")
            }
        } catch (err) {
            if (err.response?.data?.message) {
                setErreur(err.response.data.message)
            } else {
                setErreur("Erreur de connexion. Vérifiez vos identifiants.")
            }
        } finally {
            setLoading(false)
        }
    }

    return (
        <div className="login-page">
            <div className="login-card card p-4">

                {/* Logo et titre */}
                <div className="text-center mb-4">
                    <div className="mb-3">
                        <span style={{
                            fontSize: "48px",
                            background: "linear-gradient(135deg, #1a237e, #283593)",
                            WebkitBackgroundClip: "text",
                            WebkitTextFillColor: "transparent"
                        }}>📅</span>
                    </div>
                    <h3 className="fw-bold" style={{ color: "#1a237e" }}>
                        EduSchedule Pro
                    </h3>
                    <p className="text-muted small">
                        Système de Gestion de l'Emploi du Temps
                    </p>
                </div>

                {/* Message d'erreur */}
                {erreur && (
                    <div className="alert alert-danger py-2 small">
                        <i className="me-2">⚠️</i>{erreur}
                    </div>
                )}

                {/* Formulaire */}
                <form onSubmit={handleSubmit}>
                    <div className="mb-3">
                        <label className="form-label fw-semibold">
                            Adresse email
                        </label>
                        <input
                            type="email"
                            className="form-control"
                            placeholder="votre@email.bf"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            required
                        />
                    </div>

                    <div className="mb-4">
                        <label className="form-label fw-semibold">
                            Mot de passe
                        </label>
                        <input
                            type="password"
                            className="form-control"
                            placeholder="••••••••"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        className="btn w-100 text-white fw-semibold"
                        style={{ background: "linear-gradient(135deg, #1a237e, #283593)" }}
                        disabled={loading}
                    >
                        {loading ? (
                            <>
                                <span className="spinner-border spinner-border-sm me-2"></span>
                                Connexion...
                            </>
                        ) : "Se connecter"}
                    </button>
                </form>

                {/* Comptes de test */}
                <div className="mt-4 p-3 bg-light rounded">
                    <p className="small fw-semibold text-muted mb-2">
                        🔑 Comptes de démonstration :
                    </p>
                    <div className="small text-muted">
                        <div>👤 <strong>Admin :</strong> admin@isge.bf</div>
                        <div>👤 <strong>Enseignant :</strong> bere.cedric@isge.bf</div>
                        <div>👤 <strong>Délégué :</strong> delegue.l1@isge.bf</div>
                        <div>🔒 <strong>Mot de passe :</strong> password123</div>
                    </div>
                </div>

            </div>
        </div>
    )
}