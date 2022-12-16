import React from 'react';
import logo from './../../assets/adu-logo.svg'

export default function ApplicationLogo({ className, width }) {
    return (
        <img className={className} src={logo} alt="Workflow"/>
    );
}
