import React from 'react';
import Authenticated from '@/Layouts/MainLayout';
import { Head } from '@inertiajs/inertia-react';

export default function Dashboard(props) {
    return (
        <Authenticated
            errors={props.errors}
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">ADU Reports - Google Sheets</div>
                    </div>
                </div>
            </div>
        </Authenticated>
    );
}
