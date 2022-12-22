import React, { useState } from 'react'
import Authenticated from '@/Layouts/MainLayout';
import { Head } from '@inertiajs/inertia-react';
import Spinner from '@/Components/Spinner/Spinner';
import Alertmessage from '@/Components/Alertmessage/Alertmessage';
import { isEmpty } from 'lodash';
import Swal from 'sweetalert2';

export default function JobReport(props) {
	const [processing, setProcessing] = useState(false);
	const [error, setError] = useState('');
	const [rowData, setRowData] = useState([]);
	const [exportData, setExportData] = useState([]);
	
	const getEndDate = () => {
		const currDate = new Date();
		return currDate.toISOString().substring(0, 10);
	}
	const getStartDate = () => {
		const sDate = new Date(endDate);
		sDate.setDate(sDate.getDate() - 2);
		return sDate.toISOString().substring(0, 10);
	}

	const [endDate, setEndDate] = useState(getEndDate());
	const [startDate, setStartDate] = useState(getStartDate());

	const onSubmit = (e) => {
		e.preventDefault();
		setProcessing(true);
		
		try {
			if (isEmpty(startDate) || isEmpty(endDate)) {
				throw new Error('Please set start and end dates to get the report!!');
			}

			const sDate = new Date(startDate);
			const eDate = new Date(endDate);

			if (sDate > eDate) {
				throw new Error('End date should be greater than Start date!!');
			}

			fetch(`api/reports/jobreport/${startDate}/${endDate}`)
			.then(response => response.json())
			.then(response => {
				if(response.success){
					const displayData = response.data.map((item, i) => {
						const resultColor = item.jobresult === 'SUCCESS' ? 'text-green-600 bg-green-200' : 'text-red-600 bg-red-200';
						return(
							<tr key={i} className="bg-white border-b border-adu-red hover:bg-gray-50">
								<td scope="row" className="py-4 px-6 text-sm text-gray-900 whitespace-pre-wrap">{new Date(item.created_at).toLocaleString("en-US")}</td>
								<td scope="col" className="py-4 px-6 text-sm">{item.jobname}</td>
								<td><p className={`py-2 px-2 text-sm text-center uppercase rounded-full ${resultColor}`}>{item.jobresult}</p></td>
								{/* <td scope="col" className={`py-4 px-6 text-sm ${item.jobresult === 'SUCCESS' ? 'text-green-600' : 'text-red-600'}`}>{item.jobresult}</td> */}
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
				fetch(`api/reports/exportappointments`, requestOptions)
					.then(response => response.json())
					.then(response => {
						Swal.fire(
							'Exported!',
							'Appointment Report was exported succesfully to Google Sheets',
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

	const onChangeEndDate = (e) => {
		setEndDate(e.target.value);
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
										<label htmlFor="startDate" className="block text-sm font-medium text-gray-700">Start Date:</label>
										<input type="date" name="startDate" 
											onChange={onChangeStartDate}
											disabled ={processing}
											value={startDate} id="startDate" autoComplete="given-name" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
										</input>
									</div>

									<div className="col-span-6 sm:col-span-3">
										<label htmlFor="endDate" className="block text-sm font-medium text-gray-700">End Date:</label>
										<input type="date" name="endDate" 
											onChange={onChangeEndDate}
											disabled ={processing}
											value={endDate} id="endDate" autoComplete="family-name" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
										</input>
									</div>
								</div>							
								<div className="col-span-6 sm:col-span-3 flex items-center mx-6">
									<button onClick={onSubmit} className="h-10 px-6 font-semibold rounded-md bg-adu-blue text-white mx-4" type="submit">
										Search
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
					<div className="bg-white bg-adu-blue">
						{!processing && 
						<div className="overflow-x-auto bg-adu-blue">
						<table className="min-w-full">
							<thead className="text-xs bg-adu-blue">
								<tr>
									<th scope="col" className="text-sm font-medium text-white px-6 py-2">Date</th>
									<th scope="col" className="text-sm font-medium text-white px-6 py-2">Job</th>
									<th scope="col" className="text-sm font-medium text-white px-6 py-2">Status</th>
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
