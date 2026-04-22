# eBMR Design System Rules

This document defines the professional design standards for the eBMR (Electronic Batch Manufacturing Record) project. All new components and UI refactors must adhere to these rules.

## 1. Visual Theme
- **Color Palette**:
  - `primary`: `#0ea5e9` (Sky Blue) - Used for primary actions.
  - `accent`: `#22d3ee` (Cyan) - **Core Theme & Background Color**. Used for the overall template aesthetic.
  - `bg-light`: `#ecfeff` (Ultra-light Cyan) - Used for page backgrounds to maintain readability while keeping the cyan theme.
  - `bg-dark`: `#164e63` (Deep Cyan-Blue) - Used for sidebars and headers to complement the accent.
  - `text-main`: `#164e63` - Deep blue-cyan for sharp text.
- **Glassmorphism**: Use `backdrop-filter: blur(20px)` and semi-transparent backgrounds with thin borders (`rgba(255, 255, 255, 0.5)`).

## 2. Typography
- **Primary Font**: `Inter`, sans-serif (Google Fonts).
- **Weights**: 300 (Light), 400 (Regular), 500 (Medium), 600 (Semi-bold), 700 (Bold), 800 (Extra-bold).
- **Headings**: Bold/Extra-bold with tight letter-spacing (`-0.025em`) for a modern look.

## 3. UI Components
- **Buttons**:
  - Primary: Linear gradient from `primary` to `primary-dark`, rounded corners (`12px`), soft shadow.
  - Hover: Lift effect (`transform: translateY(-2px)`) and increased shadow.
- **Cards**:
  - Rounded corners: `24px`.
  - Border: `1px solid rgba(255, 255, 255, 0.5)`.
  - Shadow: Large, soft shadows (`0 25px 50px -12px rgba(0, 0, 0, 0.1)`).
- **Inputs**:
  - Rounded corners: `12px`.
  - Border: `2px solid #e2e8f0`.
  - Focus: Change border to `primary` and add a light glow/ring.

## 4. Branding
- **Logo**: Use the `iconstella.svg` logo.
- **Project Name Styling**: `eBMR` (lowercase 'e', uppercase 'BMR' in bold). The 'BMR' part should ideally use the `primary` color.

## 5. Layout Standards
- **Sidebar**: Dark theme using `bg-dark`, high contrast for active links using `primary` color.
- **Content Area**: Use a clean, light background (or a subtle laboratory-themed pattern) with glassmorphic containers.
- **Animations**: Subtle transitions (`0.3s cubic-bezier(0.4, 0, 0.2, 1)`) for all interactive elements.
