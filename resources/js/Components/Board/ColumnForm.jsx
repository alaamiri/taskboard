export default function ColumnForm({ form, onSubmit, onCancel }) {
    return (
        <form onSubmit={onSubmit} className="bg-gray-100 rounded p-4">
            <input
                type="text"
                value={form.data.name}
                onChange={(e) => form.setData('name', e.target.value)}
                placeholder="Nom de la colonne"
                className="w-full border rounded px-2 py-1 mb-2"
                autoFocus
            />
            <div className="flex gap-2">
                <button
                    type="submit"
                    className="bg-blue-500 text-white px-3 py-1 rounded text-sm"
                >
                    Ajouter
                </button>
                <button
                    type="button"
                    onClick={onCancel}
                    className="px-3 py-1 text-sm"
                >
                    Annuler
                </button>
            </div>
        </form>
    );
}
