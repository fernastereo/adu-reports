import React from 'react'
import Spinner from '@/Components/Spinner/Spinner';

export default function SyncItem(props) {
  return (
    <div className="flex justify-start text-sm font-medium">
        <div className="w-1/6 mx-6 my-6 flex items-center">
            <h2>{props.title}</h2>
        </div>
        <div className="mx-2 my-6 flex items-center">
            {props.processing && <Spinner/>}
        </div>
        <div className="w-1/6 mx-4 my-6 flex items-center">
            {props.resultSync}
        </div>
    </div>
  )
}
