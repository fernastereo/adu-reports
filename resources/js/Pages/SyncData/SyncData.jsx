import React, { useState } from 'react'
import Authenticated from '@/Layouts/MainLayout';
import { Head } from '@inertiajs/inertia-react';
import Spinner from '@/Components/Spinner/Spinner';
import Alertmessage from '@/Components/Alertmessage/Alertmessage';
import ProgressBar from '@/Components/ProgressBar/ProgressBar';
import SyncItem from '@/Components/SyncItem/SyncItem';

export default function SyncData(props) {
	const [processingCallMeeting, setProcessingCallMeeting] = useState(false);
	const [processingOnSite, setProcessingOnSite] = useState(false);
	const [processingSalesPerson, setProcessingSalesPerson] = useState(false);
	const [processingOpportunities, setProcessingOpportunities] = useState(false);
	const [processingContacts, setProcessingContacts] = useState(false);
	const [error, setError] = useState('');
	const [resultSyncCallMeeting, setResultSyncCallMeeting] = useState('');
	const [resultSyncOnSite, setResultSyncOnSite] = useState('');
	const [resultSyncSalesPerson, setResultSyncSalesPerson] = useState('');
	const [resultSyncOpportunities, setResultSyncOpportunities] = useState('');
	const [resultSyncContacts, setResultSyncContacts] = useState('');

	const closeAlert = () => {
		setError('');

	}
    
    const onSubmit = (e) => {
		e.preventDefault();
        setProcessingCallMeeting(true);
        setProcessingCallMeeting(true);
        fetch('api/sync/calendar/callMeetingCalendarId')
		.then(response => response.json())
		.then(response => {
            setResultSyncCallMeeting(response.data);
            setProcessingCallMeeting(false);
            setProcessingOnSite(true);
            return fetch('api/sync/calendar/onSiteEvaluationCalendarId')
        })
        .then(response => response.json())
		.then(response => {
            setResultSyncOnSite(response.data);
            setProcessingOnSite(false);
            setProcessingSalesPerson(true);
            return fetch('api/sync/salesperson')
        })
        .then(response => response.json())
		.then(response => {
            setResultSyncSalesPerson(response.data);
            setProcessingSalesPerson(false);
            setProcessingOpportunities(true);
            return fetch('api/sync/opportunity')
        })
        .then(response => response.json())
		.then(response => {
            setResultSyncOpportunities(response.data);
            setProcessingOpportunities(false);
            // setProcessingContacts(true);
            // return fetch('api//sync/opportunity')
        })
        ;
    }

    return (
        <Authenticated
            errors={props.errors}
        >
                <Head title="Dashboard" />

                <div className="py-2">
                    <div className="max-w-8xl mx-auto sm:px-6 lg:px-8">
                        
                        {error && <Alertmessage message={error} closeAlert={closeAlert}></Alertmessage> }
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="mt-10 sm:mt-0">
                                <SyncItem 
                                    title="CallMeeting Calendar" 
                                    onSubmit={onSubmit} 
                                    processing={processingCallMeeting}
                                    resultSync={resultSyncCallMeeting}
                                    >
                                </SyncItem>
                                <SyncItem 
                                    title="OnSite Evaluation Calendar" 
                                    onSubmit={onSubmit} 
                                    processing={processingOnSite}
                                    resultSync={resultSyncOnSite}
                                    >
                                </SyncItem>
                                <SyncItem 
                                    title="Sales Persons" 
                                    onSubmit={onSubmit} 
                                    processing={processingSalesPerson}
                                    resultSync={resultSyncSalesPerson}
                                    >
                                </SyncItem>
                                <SyncItem 
                                    title="Opportunities" 
                                    onSubmit={onSubmit} 
                                    processing={processingOpportunities}
                                    resultSync={resultSyncOpportunities}
                                    >
                                </SyncItem>
                                <SyncItem 
                                    title="Contacts" 
                                    onSubmit={onSubmit} 
                                    processing={processingContacts}
                                    resultSync={resultSyncContacts}
                                    >
                                </SyncItem>
                                <div className="flex justify-start text-sm font-medium">
                                    <div className="mx-4 my-6 flex items-center">
                                        <button onClick={onSubmit} className="h-8 px-6 font-semibold rounded-md bg-black text-white" type="submit">
                                            Sync All
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </Authenticated>
    )
}
