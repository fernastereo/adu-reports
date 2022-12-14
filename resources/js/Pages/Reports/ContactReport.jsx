import React, { useState } from 'react'
import Authenticated from '@/Layouts/MainLayout';
import { Head } from '@inertiajs/inertia-react';
import Spinner from '@/Components/Spinner/Spinner';
import Alertmessage from '@/Components/Alertmessage/Alertmessage';
import { isEmpty } from 'lodash';
import Swal from 'sweetalert2';

export default function ContactReport(props) {
	const [processing, setProcessing] = useState(false);
	const [error, setError] = useState('');
	const [rowData, setRowData] = useState([]);
	const [exportData, setExportData] = useState([]);

	const getStartDate = () => {
		const currDate = new Date();
		currDate.setMonth(currDate.getMonth() - 1);
		return currDate.toISOString().substring(0, 10);
	}

	const [startDate, setStartDate] = useState(getStartDate());
	
	const onSubmit = (e) => {
		e.preventDefault();
		setProcessing(true);
		
		try {
			if (isEmpty(startDate)) {
				throw new Error('Please set start date to get the report!!');
			}

			const sDate = new Date(startDate);
			sDate.setDate(sDate.getDate() + 1);

			fetch(`api/reports/contactreport/${startDate}`)
			.then(response => response.json())
			.then(response => {
				if(response.success){
					const displayData = response.data.map((item, i) => {
						const callMeetingColor = item.callMeeting === 'showed' ? 'text-green-600 bg-green-200' : item.callMeeting === 'confirmed' ? 'text-blue-600 bg-blue-200' : item.callMeeting === 'cancelled' ? 'text-red-600 bg-red-200' : '';
						const onSiteColor = item.onSite === 'showed' ? 'text-green-600 bg-green-200' : item.onSite === 'confirmed' ? 'text-blue-600 bg-blue-200' : item.onSite === 'cancelled' ? 'text-red-600 bg-red-200' : '';
						return(
							<tr key={i} className="bg-white border-b border-adu-red hover:bg-adu-blue-50">
								<td className="py-4 px-2 text-xs text-gray-900 whitespace-pre-wrap">{new Date(item.date).toLocaleDateString("en-US")}</td>
								<td className="py-4 px-2 text-xs">{item.customerName}</td>
								<td className="py-4 px-2 text-xs">{item.salesPerson}</td>
								<td><p className={`py-2 px-2 text-xs text-center uppercase rounded-full ${callMeetingColor}`}>{item.callMeeting}</p></td>
								<td><p className={`py-2 px-2 text-xs text-center uppercase rounded-full ${onSiteColor}`}>{item.onSite}</p></td>
								<td className="py-4 px-6 text-xs text-center font-extrabold">{item.contractSent}</td>
								<td className="py-4 px-6 text-xs text-center font-extrabold">{item.opportunityWon}</td>
								<td className="py-4 px-1 text-xs">{item.appointmentSetterNotes}</td>
								<td className="py-4 px-1 text-xs">{item.disposition}</td>
								<td className="py-4 px-1 text-xs">{item.salesPersonFeedback}</td>
							</tr>)
					});
					setExportData(response.data);
					setProcessing(false);
					setRowData(displayData);
				}else{
					console.error(response.error.message);
					setProcessing(false);
					Swal.fire({
						title: 'Error!',
						text: 'Something went wrong while pulling data. Please try again!',
						icon: 'error',
						confirmButtonText: 'Close'
					});
					setError(response.error.message);
				}
			});

			setError('');
		} catch (error) {
			setProcessing(false);
			Swal.fire({
				title: 'Error!',
				text: 'Something went wrong while pulling data. Please try again!',
				icon: 'error',
				confirmButtonText: 'Close'
			});
			setError(error.message);
		}
	}

	const onExport = (e) => {
		Swal.fire({
			title: 'Are you sure?',
			text: "You won't be able to revert this!",
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#3085d6',
			cancelButtonColor: '#d33',
			confirmButtonText: 'Yes, export data!'
		}).then((result) => {
			if (result.isConfirmed) {
				const requestOptions = {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ 'data': exportData })
				};
				fetch(`api/reports/exportcontacts`, requestOptions)
					.then(response => response.json())
					.then(response => {
						Swal.fire(
							'Exported!',
							'Contact Report was exported succesfully to Google Sheets',
							'success'
						)
					})
					.catch(error => console.error(error));
			}
		});
	}

	const onChangeStartDate = (e) => {
		setStartDate(e.target.value);
	}

	const closeAlert = () => {
		setError('');
		setProcessing(false);
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
							<div className="flex justify-between text-sm font-medium">
								<div className="flex space-x-4 mx-6 my-6">
									<div className="col-span-6 sm:col-span-3">
										<label htmlFor="startDate" className="block text-sm font-medium text-gray-700">Contacts created since:</label>
										<input type="date" name="startDate" 
											onChange={onChangeStartDate}
											disabled ={processing}
											value={startDate} id="startDate" autoComplete="given-name" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
										</input>
									</div>
								</div>							
								<div className="col-span-6 sm:col-span-3 flex items-center mx-6">
									<button onClick={onSubmit} className="h-10 px-6 font-semibold rounded-md bg-adu-blue text-white mx-4" type="submit">
										Load Data
									</button>
									<button onClick={onExport} className="h-10 px-6 font-semibold rounded-md bg-adu-blue text-white mx-4" type="submit">
										Export
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div>
				<div className="max-w-8xl mx-auto sm:px-6 lg:px-8">
					{processing && <div className="flex items-center justify-center mt-8">
						<Spinner/>
					</div>}
					<div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
						{!processing && 
						<div className="overflow-x-auto bg-adu-blue">
						<table className="table-fixed" style={{width: '100%'}}>
							<thead className="text-xs bg-adu-blue">
								<tr>
									<th className="text-sm font-medium text-white px-2 py-2" style={{width: '7%'}}>Date</th>
									<th className="text-sm font-medium text-white px-6 py-2">Customer Name</th>
									<th className="text-sm font-medium text-white px-6 py-2">Sales Person</th>
									<th className="text-sm font-medium text-white px-1 py-2" style={{width: '8%'}}>Call Meeting</th>
									<th className="text-sm font-medium text-white px-1 py-2" style={{width: '8%'}}>On Site</th>
									<th className="text-sm font-medium text-white px-1 py-2" style={{width: '7%'}}>Contract Sent</th>
									<th className="text-sm font-medium text-white px-1 py-2" style={{width: '7%'}}>Opportunity Won</th>
									<th className="text-sm font-medium text-white px-6 py-2" style={{width: '15%'}}>Appointment Setter Notes</th>
									<th className="text-sm font-medium text-white px-6 py-2" style={{width: '10%'}}>Disposition</th>
									<th className="text-sm font-medium text-white px-6 py-2" style={{width: '15%'}}>Sales Person Feedback</th>
								</tr>
							</thead>
							<tbody>
								{rowData}
							</tbody>
						</table>
						</div>}
					</div>
				</div>
			</div>
    </Authenticated>
  )
}
