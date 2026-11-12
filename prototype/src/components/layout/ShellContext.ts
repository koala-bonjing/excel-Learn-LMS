import { createContext, useContext } from 'react';

export interface ShellContextValue {
  requestLogout: () => void;
}

export const ShellContext = createContext<ShellContextValue>({
  requestLogout: () => {},
});

export const useShell = () => useContext(ShellContext);
