// =============================================
// EduSchedule Pro - Layout Dashboard
// =============================================

import { Link, useNavigate, useLocation } from "react-router-dom"
import { useAuth } from "../context/AuthContext"

export default function DashboardLayout({ children }) {
    const { utilisateur, logout } = useAuth()
    const navigate = useNavigate()
    const location = useLocation()

    const handleLogout = () => {
        logout()
        navigate("/login")
    }

    // Menus selon le rôle
    const menusAdmin = [
        { icon: "📊", label: "Tableau de bord", path: "/dashboard/admin" },
        { icon: "📅", label: "Emploi du temps", path: "/emploi-temps" },
        { icon: "📝", label: "Cahiers de texte", path: "/cahiers" },
        { icon: "👨‍🏫", label: "Enseignants", path: "/enseignants" },
        { icon: "🎓", label: "Classes", path: "/classes" },
        { icon: "📚", label: "Matières", path: "/matieres" },
        { icon: "🏫", label: "Salles", path: "/salles" },
        { icon: "📋", label: "Pointages", path: "/pointages" },
    ]

    const menusEnseignant = [
        { icon: "📊", label: "Tableau de bord", path: "/dashboard/enseignant" },
        { icon: "📅", label: "Mes séances", path: "/emploi-temps-view" },
        { icon: "📝", label: "Cahiers de texte", path: "/cahiers" },
        { icon: "💰", label: "Mes vacations", path: "/mes-vacations" },
    ]

    const menusDelegue = [
        { icon: "📊", label: "Tableau de bord", path: "/dashboard/delegue" },
        { icon: "📅", label: "Emploi du temps", path: "/emploi-temps-view" },
        { icon: "📝", label: "Cahiers de texte", path: "/cahiers" },
        { icon: "📜", label: "Historique", path: "/cahiers" },
    ]

    const menusSurveillant = [
        { icon: "📊", label: "Tableau de bord", path: "/dashboard/surveillant" },
        { icon: "✅", label: "Contrôle pointages", path: "/pointages" },
        { icon: "📋", label: "Fiches vacation", path: "/vacations" },
    ]

    const menusComptable = [
        { icon: "📊", label: "Tableau de bord", path: "/dashboard/comptable" },
        { icon: "💰", label: "Vacations", path: "/vacations" },
    ]

    const getMenus = () => {
        switch (utilisateur?.role) {
            case "admin": return menusAdmin
            case "enseignant": return menusEnseignant
            case "delegue": return menusDelegue
            case "surveillant": return menusSurveillant
            case "comptable": return menusComptable
            default: return []
        }
    }

    const getRoleBadge = () => {
        const badges = {
            admin: { label: "Administrateur", color: "#dc3545" },
            enseignant: { label: "Enseignant", color: "#0d6efd" },
            delegue: { label: "Délégué", color: "#198754" },
            surveillant: { label: "Surveillant", color: "#fd7e14" },
            comptable: { label: "Comptable", color: "#6f42c1" },
        }
        return badges[utilisateur?.role] || { label: utilisateur?.role, color: "#6c757d" }
    }

    const badge = getRoleBadge()

    return (
        <div className="d-flex">

            {/* Sidebar */}
            <div className="sidebar d-flex flex-column p-0">

                {/* Logo */}
                <div className="p-3 border-bottom border-white border-opacity-25">
                    <div className="text-center">
                        <div style={{ fontSize: "32px" }}>📅</div>
                        <h6 className="fw-bold mb-0">EduSchedule Pro</h6>
                        <small style={{ opacity: 0.7 }}>ISGE-BF</small>
                    </div>
                </div>

                {/* Infos utilisateur */}
                <div className="p-3 border-bottom border-white border-opacity-25">
                    <div className="d-flex align-items-center gap-2">
                        <div className="rounded-circle d-flex align-items-center 
                                        justify-content-center text-white fw-bold"
                            style={{
                                width: "40px", height: "40px",
                                background: badge.color,
                                fontSize: "16px"
                            }}>
                            {utilisateur?.email?.[0]?.toUpperCase()}
                        </div>
                        <div>
                            <div className="small fw-semibold">
                                {utilisateur?.email}
                            </div>
                            <span className="badge rounded-pill small"
                                style={{ background: badge.color }}>
                                {badge.label}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Navigation */}
                <nav className="flex-grow-1 py-2">
                    {getMenus().map((menu, index) => (
                        <Link
                            key={index}
                            to={menu.path}
                            className={`nav-link d-flex align-items-center gap-2 
                                ${location.pathname === menu.path ? "active" : ""}`}
                        >
                            <span>{menu.icon}</span>
                            <span>{menu.label}</span>
                        </Link>
                    ))}
                </nav>

                {/* Déconnexion */}
                <div className="p-3 border-top border-white border-opacity-25">
                    <button
                        onClick={handleLogout}
                        className="btn btn-outline-light btn-sm w-100"
                    >
                        🚪 Se déconnecter
                    </button>
                </div>
            </div>

            {/* Contenu principal */}
            <div className="main-content flex-grow-1">
                {children}
            </div>

        </div>
    )
}