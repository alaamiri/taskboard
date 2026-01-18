export default function ImportForm({ form, boardId, onSuccess }) {
    function handleSubmit(e) {
        e.preventDefault();
        form.post(route('boards.import', boardId), {
            forceFormData: true,
            onSuccess: onSuccess
        });
    }

    return (
        <form onSubmit={handleSubmit} className="mb-4 p-4 bg-gray-100 rounded">
            <div className="flex gap-4 items-center">
                <input
                    type="file"
                    accept=".csv"
                    onChange={(e) => form.setData('file', e.target.files[0])}
                    className="border rounded px-2 py-1"
                />
                <button
                    type="submit"
                    disabled={form.processing}
                    className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 disabled:opacity-50"
                >
                    {form.processing ? 'Import...' : 'Importer'}
                </button>
            </div>
            <p className="text-sm text-gray-500 mt-2">
                Format CSV : title,description (une tâche par ligne)
            </p>
        </form>
    );
}
