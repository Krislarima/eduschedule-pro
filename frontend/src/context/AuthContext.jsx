// =============================================
// EduSchedule Pro - Contexte Authentification
// =============================================

import { createContext, useContext, useState, useEffect } from "react"

const AuthContext = createContext()

export function AuthProvider({ children }) {
    const [utilisateur, setUtilisateur] = useState(null)
    const [token, setToken] = useState(null)
    const [loading, setLoading] = useState(true)

    // Charger l'utilisateur depuis localStorage au démarrage
    useEffect(() => {
        const tokenSauvegarde = localStorage.getItem('token')
        const userSauvegarde = localStorage.getItem('utilisateur')
        if (tokenSauvegarde && userSauvegarde) {
            setToken(tokenSauvegarde)
            setUtilisateur(JSON.parse(userSauvegarde))
        }
        setLoading(false)
    }, [])

    // Connexion
    const login = (userData, userToken) => {
        setUtilisateur(userData)
        setToken(userToken)
        localStorage.setItem('token', userToken)
        localStorage.setItem('utilisateur', JSON.stringify(userData))
    }

    // Déconnexion
    const logout = () => {
        setUtilisateur(null)
        setToken(null)
        localStorage.removeItem('token')
        localStorage.removeItem('utilisateur')
    }

    // Vérifier si connecté
    const estConnecte = () => {
        return token !== null && utilisateur !== null
    }

    return (
        <AuthContext.Provider value={{
            utilisateur,
            token,
            loading,
            login,
            logout,
            estConnecte
            
        }}>
            {children}
        </AuthContext.Provider>
    )
}

// Hook personnalisé
export function useAuth() {
    return useContext(AuthContext)
}