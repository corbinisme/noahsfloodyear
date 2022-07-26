import React from "react";
import ReactDOM from "react-dom";
import Page from './components/Page';

import { createRoot } from 'react-dom/client';
const container = document.getElementById('basic-app');
const root = createRoot(container); // createRoot(container!) if you use TypeScript
root.render(<Page  />);


