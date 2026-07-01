'use client';
import React, { createContext, useContext, useState, ReactNode } from 'react';
import { api } from '@/lib/api-client';

interface User { id: string; name: string; email: string; }
interface AuthState { user: User | null; isLoading: boolean; }

const AuthContext = createContext<{
  user: User | null;
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
}>({ user: null, login: async () => {}, logout: () => {} });

export function AuthProvider({ children }: { children: ReactNode }) {
  const [state, setState] = useState<AuthState>({ user: null, isLoading: true });
  
  const login = async (email: string, password: string) => {
    const res = await api.post<{ access_token: string; token_type: string; user: User }>('/auth/login', { email, password });
    api.setToken(res.data.access_token);
    setState({ user: res.data.user, isLoading: false });
  };

  const logout = () => {
    api.clearToken();
    setState({ user: null, isLoading: false });
  };

  return (
    <AuthContext.Provider value={{ user: state.user, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);
