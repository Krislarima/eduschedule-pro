// =============================================
// EduSchedule Pro - Routing Principal
// =============================================

import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom"
import { AuthProvider } from "./context/AuthContext"
import PrivateRoute from "./components/PrivateRoute"
import LoginPage from "./pages/LoginPage"
import DashboardAdmin from "./pages/DashboardAdmin"
import DashboardEnseignant from "./pages/DashboardEnseignant"
import DashboardDelegue from "./pages/DashboardDelegue"
import EmploiTempsPage from "./pages/EmploiTempsPage"

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>

          {/* Route publique */}
          <Route path="/login" element={<LoginPage />} />

          {/* Redirection racine */}
          <Route path="/" element={<Navigate to="/login" replace />} />

          {/* Routes Admin */}
          <Route path="/dashboard/admin" element={
            <PrivateRoute roles={["admin"]}>
              <DashboardAdmin />
            </PrivateRoute>
          } />

          {/* Routes Enseignant */}
          <Route path="/dashboard/enseignant" element={
            <PrivateRoute roles={["enseignant"]}>
              <DashboardEnseignant />
            </PrivateRoute>
          } />

          {/* Routes Délégué */}
          <Route path="/dashboard/delegue" element={
            <PrivateRoute roles={["delegue"]}>
              <DashboardDelegue />
            </PrivateRoute>
          } />

          {/* Emploi du temps - accessible à tous */}
          <Route path="/emploi-temps" element={
            <PrivateRoute roles={["admin","enseignant","delegue","surveillant","etudiant"]}>
              <EmploiTempsPage />
            </PrivateRoute>
          } />

          {/* Redirection par défaut */}
          <Route path="*" element={<Navigate to="/login" replace />} />

        </Routes>
      </BrowserRouter>
    </AuthProvider>
  )
}